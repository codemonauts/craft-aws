<?php

namespace codemonauts\aws\traits;

use Aws\Result;
use Aws\ResultPaginator;
use Aws\S3\Exception\S3Exception;
use Aws\S3\PostObjectV4;
use Aws\S3\S3Client;

trait S3Trait
{
    /**
     * Copy object on same S3 bucket
     *
     * @param string $bucket The bucket to work on
     * @param string $sourceKey Source key of object to copy without leading slash and bucket name
     * @param string $destKey Destination key without leading slash
     * @param string $contentType Object's content mime type
     *
     * @return Result
     */
    public function copyOnSameBucket(string $bucket, string $sourceKey, string $destKey, string $contentType): Result
    {
        return $this->client->copyObject([
            'Bucket' => $bucket,
            'CopySource' => $bucket . '/' . $sourceKey,
            'Key' => $destKey,
            'ContentType' => $contentType,
        ]);
    }

    /**
     * Download object from S3 bucket
     *
     * @param string $bucket The source bucket
     * @param string $key Source key of object without leading slash
     * @param string $destination Path to file to save to
     *
     * @return Result
     */
    public function download(string $bucket, string $key, string $destination): Result
    {
        return $this->client->getObject([
            'Bucket' => $bucket,
            'Key' => $key,
            'SaveAs' => $destination,
        ]);
    }

    /**
     * Upload string as new object to S3 bucket
     *
     * @param string $bucket The destination bucket
     * @param string $key Destination key without leading slash
     * @param string $body String to upload
     * @param string $contentType Object's content mime type
     *
     * @return Result
     */
    public function uploadBody(string $bucket, string $key, string $body, string $contentType): Result
    {
        return $this->client->putObject([
            'Bucket' => $bucket,
            'Key' => $key,
            'Body' => $body,
            'ContentType' => $contentType,
        ]);
    }

    /**
     * Upload file as new object to S3 bucket (as multipart when needed)
     *
     * @param string $bucket The destination bucket
     * @param string $key Destination key without leading slash
     * @param string $source Path to source file
     * @param array $headers Array of headers to add to the object on upload.
     *
     * @return Result
     */
    public function uploadFile(string $bucket, string $key, string $source, array $headers = []): Result
    {
        $options = array_merge($headers, [
            'Bucket' => $bucket,
            'Key' => $key,
            'SourceFile' => $source,
        ]);

        if (filesize($source) < 500000000) {
            return $this->client->putObject($options);
        }

        $options = array_merge($headers, [
            'Bucket' => $bucket,
            'Key' => $key,
        ]);

        $response = $this->client->createMultipartUpload($options);

        $uploadId = $response['UploadId'];

        $file = fopen($source, 'rb');
        $parts = [];
        $partNumber = 1;

        while (!feof($file)) {
            $result = $this->client->uploadPart([
                'Bucket' => $bucket,
                'Key' => $key,
                'UploadId' => $uploadId,
                'PartNumber' => $partNumber,
                'Body' => fread($file, 100 * 1024 * 1024),
            ]);

            $parts[] = [
                'PartNumber' => $partNumber++,
                'ETag' => $result['ETag'],
            ];
        }

        fclose($file);

        return $this->client->completeMultipartUpload([
            'Bucket' => $bucket,
            'Key' => $key,
            'UploadId' => $uploadId,
            'MultipartUpload' => [
                'Parts' => $parts,
            ],
        ]);
    }

    /**
     * Delete object from S§ bucket
     *
     * @param string $bucket The S3 bucket
     * @param string $key Key of object to delete
     *
     * @return Result
     */
    public function delete(string $bucket, string $key): Result
    {
        return $this->client->deleteObject([
            'Bucket' => $bucket,
            'Key' => $key,
        ]);
    }

    /**
     * List all objects of a bucket
     *
     * @param string $bucket The bucket to list all objects
     * @param string $prefix Optional prefix to filter list
     *
     * @return ResultPaginator
     */
    public function listObjects(string $bucket, string $prefix = ''): ResultPaginator
    {
        return $this->client->getPaginator('ListObjects', [
            'Bucket' => $bucket,
            'Prefix' => $prefix,
        ]);
    }

    /**
     * Get the metadata of an object
     *
     * @param string $bucket The bucket where the object is located
     * @param string $key The key of the object
     *
     * @return Result|bool
     */
    public function getObjectInfo(string $bucket, string $key): Result|bool
    {
        try {
            return $this->client->headObject([
                'Bucket' => $bucket,
                'Key' => $key,
            ]);
        } catch (S3Exception) {
            return false;
        }
    }

    /**
     * Returns an upload form with signed POST request to upload files to S3.
     *
     * @param string $bucket The S3 bucket to store the object.
     * @param string $key The key to store the object.
     * @param string $redirect The URL to redirect the browser after successful upload.
     * @param string $acl The ACL to use for the uploaded object.
     * @param string $contentType The Content-Type to set for the uploaded object.
     *
     * @return \Aws\S3\PostObjectV4
     */
    public function getUploadForm(string $bucket, string $key, string $redirect, string $acl, string $contentType): PostObjectV4
    {
        $formInputs = [
            'acl' => $acl,
            'success_action_redirect' => $redirect,
            'key' => $key,
        ];

        $options = [
            ['acl' => $acl],
            ['bucket' => $bucket],
            ['starts-with', '$key', $key],
            ['starts-with', '$Content-Type', $contentType],
            ['starts-with', '$success_action_redirect', $redirect],
        ];

        return new PostObjectV4(
            $this->client,
            $bucket,
            $formInputs,
            $options
        );
    }

    /**
     * Factory S3 client
     *
     * @return void
     */
    private function createClient(): void
    {
        $config = [
            'signature' => 'v4',
            'version' => 'latest',
            'region' => $this->region,
        ];

        if (!empty($this->key)) {
            $config['credentials'] = [
                'key' => $this->key,
                'secret' => $this->secret,
            ];
        }

        $this->client = new S3Client($config);
    }
}
