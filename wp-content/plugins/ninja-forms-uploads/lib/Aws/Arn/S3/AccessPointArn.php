<?php

namespace NF_FU_LIB\Aws\Arn\S3;

use NF_FU_LIB\Aws\Arn\AccessPointArn as BaseAccessPointArn;
use NF_FU_LIB\Aws\Arn\ArnInterface;
use NF_FU_LIB\Aws\Arn\Exception\InvalidArnException;
/**
 * @internal
 */
class AccessPointArn extends BaseAccessPointArn implements ArnInterface
{
    /**
     * Validation specific to AccessPointArn
     *
     * @param array $data
     */
    protected static function validate(array $data)
    {
        parent::validate($data);
        if ($data['service'] !== 's3') {
            throw new InvalidArnException("The 3rd component of an S3 access" . " point ARN represents the region and must be 's3'.");
        }
    }
}
