<?php

namespace codemonauts\aws\traits;

use Aws\Result;
use Aws\ResultPaginator;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

trait S3Trait
{
    /**
     * Copy object on same S3 bucket
     *
     * @param string $bucket      The bucket to work on
     * @param string $sourceKey   Source key of object to copy without leading slash and bucket name
     * @param string $destKey     Destination key without leading slash
     * @param string $contentType Object's content mime type
     *
     * @return Result
     */
    public function copyOnSameBucket($bucket, $sourceKey, $destKey, $contentType): Result
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
     * @param string $bucket      The source bucket
     * @param string $key         Source key of object without leading slash
     * @param string $destination Path to file to save to
     *
     * @return Result
     */
    public function download($bucket, $key, $destination): Result
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
     * @param string $bucket      The destination bucket
     * @param string $key         Destination key without leading slash
     * @param string $body        String to upload
     * @param string $contentType Object's content mime type
     *
     * @return Result
     */
    public function uploadBody($bucket, $key, $body, $contentType): Result
    {
        return $this->client->putObject([
            'Bucket' => $bucket,
            'Key' => $key,
            'Body' => (string)$body,
            'ContentType' => $contentType,
        ]);
    }

    /**
     * Upload file as new object to S3 bucket (as multipart when needed)
     *
     * @param string $bucket      The destination bucket
     * @param string $key         Destination key without leading slash
     * @param string $source      Path to source file
     * @param string $contentType Object's content mime type
     *
     * @return Result
     */
    public function uploadFile($bucket, $key, $source, $contentType): Result
    {
        if (filesize($source) < 500000000) {
            return $this->client->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'SourceFile' => $source,
                'ContentType' => $contentType,
            ]);
        }

        $response = $this->client->createMultipartUpload([
            'Bucket' => $bucket,
            'Key' => $key,
            'ContentType' => $contentType,
        ]);

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
     * @param string $key    Key of object to delete
     *
     * @return Result
     */
    public function delete($bucket, $key): Result
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
    public function listObjects($bucket, $prefix = ''): ResultPaginator
    {
        return $this->client->getPaginator('ListObjects', [
            'Bucket' => $bucket,
            'Prefix' => $prefix,
        ]);
    }

    /**
     * Get the meta data of an object
     *
     * @param string $bucket The bucket where the object is located
     * @param string $key    The key of the object
     *
     * @return Result|bool
     */
    public function getObjectInfo($bucket, $key)
    {
        try {
            return $this->client->headObject([
                'Bucket' => $bucket,
                'Key' => $key,
            ]);
        } catch (S3Exception $e) {
            return false;
        }
    }

    /**
     * Factory S3 client
     *
     * @return S3Client
     */
    private function getClient(): S3Client
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

        return new S3Client($config);
    }
}
