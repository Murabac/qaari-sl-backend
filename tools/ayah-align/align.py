#!/usr/bin/env python3
"""
Align Quran ayahs to a surah audio file using FFmpeg silence detection.

Outputs JSON:
{
  "method": "ffmpeg_silence",
  "duration_ms": 12345,
  "timings": [{"ayah": 1, "start_ms": 0, "end_ms": 1200}, ...]
}
"""

from __future__ import annotations

import argparse
import json
import re
import subprocess
import sys
import tempfile
from pathlib import Path


SILENCE_START = re.compile(r"silence_start:\s*([0-9.]+)")
SILENCE_END = re.compile(r"silence_end:\s*([0-9.]+)")


def run(cmd: list[str]) -> subprocess.CompletedProcess[str]:
    return subprocess.run(
        cmd,
        check=False,
        capture_output=True,
        text=True,
        encoding="utf-8",
        errors="replace",
    )


def probe_duration_ms(ffprobe: str, audio: Path) -> int:
    result = run(
        [
            ffprobe,
            "-v",
            "error",
            "-show_entries",
            "format=duration",
            "-of",
            "default=noprint_wrappers=1:nokey=1",
            str(audio),
        ]
    )
    if result.returncode != 0:
        raise RuntimeError(f"ffprobe failed: {result.stderr.strip()}")
    seconds = float((result.stdout or "0").strip() or "0")
    return max(0, int(round(seconds * 1000)))


def convert_wav(ffmpeg: str, audio: Path, wav: Path) -> None:
    result = run(
        [
            ffmpeg,
            "-y",
            "-i",
            str(audio),
            "-ac",
            "1",
            "-ar",
            "16000",
            "-vn",
            str(wav),
        ]
    )
    if result.returncode != 0:
        raise RuntimeError(f"ffmpeg convert failed: {result.stderr[-800:]}")


def detect_silences(ffmpeg: str, wav: Path, noise_db: float, min_silence: float) -> list[tuple[float, float]]:
    result = run(
        [
            ffmpeg,
            "-i",
            str(wav),
            "-af",
            f"silencedetect=noise={noise_db}dB:d={min_silence}",
            "-f",
            "null",
            "-",
        ]
    )
    # silencedetect logs go to stderr
    log = (result.stderr or "") + "\n" + (result.stdout or "")
    starts: list[float] = []
    ends: list[float] = []
    for line in log.splitlines():
        m = SILENCE_START.search(line)
        if m:
            starts.append(float(m.group(1)))
            continue
        m = SILENCE_END.search(line)
        if m:
            ends.append(float(m.group(1)))

    pairs: list[tuple[float, float]] = []
    # Pair starts/ends in order
    i = j = 0
    while i < len(starts) and j < len(ends):
        if ends[j] < starts[i]:
            j += 1
            continue
        pairs.append((starts[i], ends[j]))
        i += 1
        j += 1
    return pairs


def speech_segments(duration_s: float, silences: list[tuple[float, float]]) -> list[tuple[float, float]]:
    segments: list[tuple[float, float]] = []
    cursor = 0.0
    for start, end in silences:
        if start > cursor + 0.05:
            segments.append((cursor, start))
        cursor = max(cursor, end)
    if duration_s > cursor + 0.05:
        segments.append((cursor, duration_s))
    if not segments:
        segments = [(0.0, duration_s)]
    # Drop tiny noise blips
    cleaned = [(a, b) for a, b in segments if (b - a) >= 0.12]
    return cleaned or [(0.0, duration_s)]


def weights_from_lengths(lengths: list[int]) -> list[float]:
    total = float(sum(max(1, n) for n in lengths)) or float(len(lengths))
    return [max(1, n) / total for n in lengths]


def map_segments_to_ayahs(
    segments: list[tuple[float, float]],
    verse_count: int,
    lengths: list[int],
    duration_s: float,
) -> list[tuple[float, float]]:
    if verse_count <= 0:
        return []

    weights = weights_from_lengths(lengths or [1] * verse_count)
    if len(weights) != verse_count:
        weights = [1.0 / verse_count] * verse_count

    # If we got roughly one speech island per ayah, map directly (with pad/trim).
    if abs(len(segments) - verse_count) <= max(1, verse_count // 10) and len(segments) >= max(1, verse_count - 2):
        # Merge or split segment list to exact verse_count
        segs = list(segments)
        while len(segs) > verse_count:
            # merge shortest adjacent pair
            best_i = 0
            best_len = float("inf")
            for i in range(len(segs) - 1):
                merged = segs[i + 1][1] - segs[i][0]
                if merged < best_len:
                    best_len = merged
                    best_i = i
            a0, _ = segs[best_i]
            _, b1 = segs[best_i + 1]
            segs = segs[:best_i] + [(a0, b1)] + segs[best_i + 2 :]
        while len(segs) < verse_count:
            # split longest segment
            longest = max(range(len(segs)), key=lambda i: segs[i][1] - segs[i][0])
            a, b = segs[longest]
            mid = (a + b) / 2.0
            segs = segs[:longest] + [(a, mid), (mid, b)] + segs[longest + 1 :]
        return segs[:verse_count]

    # Fallback: weighted split of full duration using silence boundaries as snap points.
    boundaries = [0.0]
    for start, end in silences_midpoints(segments, duration_s):
        if 0.15 < start < duration_s - 0.15:
            boundaries.append(start)
    boundaries.append(duration_s)
    boundaries = sorted(set(round(b, 3) for b in boundaries))

    targets = []
    acc = 0.0
    for w in weights[:-1]:
        acc += w
        targets.append(acc * duration_s)

    chosen = [0.0]
    used = {0.0, duration_s}
    for t in targets:
        # nearest unused boundary
        candidates = [b for b in boundaries if b not in used]
        if not candidates:
            chosen.append(t)
            continue
        nearest = min(candidates, key=lambda b: abs(b - t))
        # Prefer silence snap if reasonably close, else target
        if abs(nearest - t) <= max(0.75, duration_s * 0.02):
            chosen.append(nearest)
            used.add(nearest)
        else:
            chosen.append(t)
    chosen.append(duration_s)
    chosen = sorted(chosen)

    out: list[tuple[float, float]] = []
    for i in range(verse_count):
        a = chosen[i]
        b = chosen[i + 1]
        if b <= a:
            b = min(duration_s, a + 0.2)
        out.append((a, b))
    # Ensure last ends at duration
    if out:
        a, _ = out[-1]
        out[-1] = (a, duration_s)
    return out


def silences_midpoints(segments: list[tuple[float, float]], duration_s: float) -> list[tuple[float, float]]:
    # Use gaps between speech segments as silence midpoints for snapping
    mids: list[tuple[float, float]] = []
    for i in range(len(segments) - 1):
        gap_start = segments[i][1]
        gap_end = segments[i + 1][0]
        mid = (gap_start + gap_end) / 2.0
        mids.append((mid, mid))
    return mids


def equal_split(verse_count: int, duration_s: float) -> list[tuple[float, float]]:
    if verse_count <= 0:
        return []
    step = duration_s / verse_count
    return [(i * step, (i + 1) * step if i < verse_count - 1 else duration_s) for i in range(verse_count)]


def main() -> int:
    parser = argparse.ArgumentParser(description="Align ayahs to surah audio")
    parser.add_argument("--audio", required=True)
    parser.add_argument("--verses", type=int, required=True)
    parser.add_argument("--lengths", default="", help="Comma-separated character lengths per ayah")
    parser.add_argument("--surah", type=int, default=0, help="Surah number (for basmala offset)")
    parser.add_argument("--ffmpeg", default="ffmpeg")
    parser.add_argument("--ffprobe", default="ffprobe")
    parser.add_argument("--noise-db", type=float, default=-42.0)
    parser.add_argument("--min-silence", type=float, default=0.12)
    parser.add_argument("--out", required=True)
    args = parser.parse_args()

    audio = Path(args.audio)
    if not audio.is_file():
        print(json.dumps({"error": f"Audio not found: {audio}"}), file=sys.stderr)
        return 2

    lengths = [int(x) for x in args.lengths.split(",") if x.strip().isdigit()] if args.lengths else []
    duration_ms = probe_duration_ms(args.ffprobe, audio)
    duration_s = max(0.001, duration_ms / 1000.0)

    method = "equal_split"
    timings_s: list[tuple[float, float]]

    with tempfile.TemporaryDirectory(prefix="qaari-align-") as tmp:
        wav = Path(tmp) / "audio.wav"
        try:
            convert_wav(args.ffmpeg, audio, wav)
            all_silences: list[tuple[float, float]] = []
            for noise, mind in [(-35.0, 0.22), (-42.0, 0.14), (-50.0, 0.08), (-55.0, 0.06)]:
                all_silences.extend(detect_silences(args.ffmpeg, wav, noise, mind))
            # also honor CLI defaults
            all_silences.extend(detect_silences(args.ffmpeg, wav, args.noise_db, args.min_silence))

            # merge
            all_silences.sort(key=lambda p: p[0])
            merged: list[tuple[float, float]] = []
            for start, end in all_silences:
                if not merged or start > merged[-1][1] + 0.12:
                    merged.append((start, end))
                else:
                    merged[-1] = (merged[-1][0], max(merged[-1][1], end))

            content_start = 0.0
            if args.surah not in (0, 1, 9):
                best_len = 0.0
                for start, end in merged:
                    if 1.8 <= start <= 14.0 and end < duration_s - 1.0:
                        length = end - start
                        if length >= 0.18 and length > best_len:
                            best_len = length
                            content_start = end

            content_duration = max(0.5, duration_s - content_start)
            relative = [
                (max(0.0, s - content_start), max(0.0, e - content_start))
                for s, e in merged
                if e > content_start + 0.05
            ]
            segments = speech_segments(content_duration, relative)
            mapped = map_segments_to_ayahs(segments, args.verses, lengths, content_duration)
            timings_s = [(a + content_start, b + content_start) for a, b in mapped]
            if timings_s:
                timings_s[0] = (content_start, timings_s[0][1])
                timings_s[-1] = (timings_s[-1][0], duration_s)
            method = "ffmpeg_silence_basmala" if content_start > 0.5 else "ffmpeg_silence"
        except Exception as exc:  # noqa: BLE001
            timings_s = equal_split(args.verses, duration_s)
            method = f"equal_split_fallback:{exc}"

    if len(timings_s) != args.verses:
        timings_s = equal_split(args.verses, duration_s)
        method = "equal_split"

    payload = {
        "method": method,
        "duration_ms": duration_ms,
        "timings": [
            {
                "ayah": i + 1,
                "start_ms": int(round(a * 1000)),
                "end_ms": int(round(b * 1000)),
            }
            for i, (a, b) in enumerate(timings_s)
        ],
    }

    out = Path(args.out)
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(json.dumps(payload), encoding="utf-8")
    print(json.dumps({"ok": True, "method": method, "count": len(payload["timings"])}))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
