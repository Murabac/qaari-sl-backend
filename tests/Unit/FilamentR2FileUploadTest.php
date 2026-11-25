<?php

namespace Tests\Unit;

use App\Support\FilamentR2FileUpload;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use ReflectionProperty;
use Tests\TestCase;

class FilamentR2FileUploadTest extends TestCase
{
    public function test_it_disables_file_information_fetch_for_private_r2(): void
    {
        $upload = FilamentR2FileUpload::configure(
            FileUpload::make('audio_url')->directory('recitations/audio'),
        );

        $this->assertSame('r2', $upload->getDiskName());
        $this->assertSame('private', $upload->getVisibility());
        $this->assertFalse($upload->shouldFetchFileInformation());
        $this->assertFalse($upload->isPreviewable());
        $this->assertFalse($upload->isOpenable());
        $this->assertFalse($upload->isDownloadable());
    }

    public function test_existing_photo_is_described_without_a_remote_url(): void
    {
        $upload = FilamentR2FileUpload::configure(
            FileUpload::make('photo_url')->image(),
        );

        $described = $this->describe($upload, 'reciters/photos/sheikh.png');

        $this->assertSame('sheikh.png', $described['name']);
        $this->assertSame(1, $described['size']);
        $this->assertSame('image/png', $described['type']);
        $this->assertSame('reciters/photos/sheikh.png', $described['url']);
        $this->assertStringStartsNotWith('http', $described['url']);
    }

    public function test_existing_audio_mp4_uses_an_accepted_audio_type(): void
    {
        $upload = FilamentR2FileUpload::configure(
            FileUpload::make('audio_url')->acceptedFileTypes([
                'audio/mpeg',
                'audio/mp4',
            ]),
        );

        $described = $this->describe($upload, 'recitations/audio/surah.mp4', [
            'recitations/audio/surah.mp4' => 'Al-Fatiha.mp4',
        ]);

        $this->assertSame('Al-Fatiha.mp4', $described['name']);
        $this->assertSame('audio/mp4', $described['type']);
        $this->assertSame('recitations/audio/surah.mp4', $described['url']);
    }

    /**
     * @param  array<string, string>|string|null  $storedFileNames
     * @return array{name: string, size: int, type: string, url: string}
     */
    private function describe(FileUpload $upload, string $file, array|string|null $storedFileNames = null): array
    {
        $property = new ReflectionProperty(BaseFileUpload::class, 'getUploadedFileUsing');
        $callback = $property->getValue($upload);

        return $callback($upload, $file, $storedFileNames);
    }
}
