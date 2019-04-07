<?php

namespace codemonauts\aws\services;

use Aws\S3\Exception\S3Exception;
use codemonauts\aws\Aws;
use yii\base\Component;
use Aws\S3\S3Client;
use Aws\Result;
use Aws\ResultPaginator;

class S3 extends Component
{
    private $client = null;
    private $key = '';
    private $secret = '';
    private $region = '';

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->key = Aws::getInstance()->getSettings()->key;
        $this->secret = Aws::getInstance()->getSettings()->secret;
        $this->region = Aws::getInstance()->getSettings()->region;

        $this->client = $this->getClient();
    }

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
    public function copyOnSameBucket($bucket, $sourceKey, $destKey, $contentType)
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
    public function download($bucket, $key, $destination)
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
    public function uploadBody($bucket, $key, $body, $contentType)
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
    public function uploadFile($bucket, $key, $source, $contentType)
    {
        if (filesize($source) < 500000000) {
            return $this->client->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'SourceFile' => $source,
                'ContentType' => $contentType,
            ]);
        } else {
            $response = $this->client->createMultipartUpload([
                'Bucket' => $bucket,
                'Key' => $key,
                'ContentType' => $contentType,
            ]);

            $uploadId = $response['UploadId'];

            $file = fopen($source, 'r');
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
    }

    /**
     * Delete object from S§ bucket
     *
     * @param string $bucket The S3 bucket
     * @param string $key    Key of object to delete
     *
     * @return Result
     */
    public function delete($bucket, $key)
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
     *
     * @return ResultPaginator
     */
    public function listObjects($bucket)
    {
        return $this->client->getPaginator('ListObjects', [
            'Bucket' => $bucket,
        ]);
    }

    /**
     * Get the meta data of an object
     *
     * @param string $bucket The bucket where the object is located
     * @param string $key    The key of the object
     *
     * @return Result
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
    private function getClient()
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
