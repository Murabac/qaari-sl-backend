<?php

namespace Tests\Unit;

use App\Support\FilamentR2FileUpload;
use Filament\Forms\Components\FileUpload;
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
}
