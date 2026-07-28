<?php

namespace App\Livewire;

use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl as BaseGenerateSignedUploadUrl;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

use function Livewire\invade;

/**
 * R2 (and similar S3-compatible stores) reject x-amz-acl on presigned uploads.
 */
class GenerateSignedUploadUrl extends BaseGenerateSignedUploadUrl
{
    public function forS3($file, $visibility = 'private')
    {
        $storage = FileUploadConfiguration::storage();

        $driver = $storage->getDriver();
        $adapter = invade($driver)->adapter;
        $client = invade($adapter)->client;
        $bucket = invade($adapter)->bucket;

        $fileType = $file->getMimeType();
        $fileHashName = TemporaryUploadedFile::generateHashNameWithOriginalNameEmbedded($file);
        $path = FileUploadConfiguration::path($fileHashName);

        $command = $client->getCommand('putObject', array_filter([
            'Bucket' => $bucket,
            'Key' => $path,
            'ContentType' => $fileType ?: 'application/octet-stream',
            'CacheControl' => null,
            'Expires' => null,
        ]));

        $signedRequest = $client->createPresignedRequest(
            $command,
            '+'.FileUploadConfiguration::maxUploadTime().' minutes'
        );

        $uri = $signedRequest->getUri();

        if (filled($url = $storage->getConfig()['temporary_url'] ?? null)) {
            $uri = invade($storage)->replaceBaseUrl($uri, $url);
        }

        return [
            'path' => TemporaryUploadedFile::signPath($fileHashName),
            'url' => (string) $uri,
            'headers' => $this->headers($signedRequest, $fileType),
        ];
    }
}
