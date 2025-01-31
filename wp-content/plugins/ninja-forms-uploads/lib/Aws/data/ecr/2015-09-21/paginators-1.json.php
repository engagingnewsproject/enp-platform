<?php

namespace NF_FU_LIB;

// This file was auto-generated from sdk-root/src/data/ecr/2015-09-21/paginators-1.json
return ['pagination' => ['DescribeImageScanFindings' => ['input_token' => 'nextToken', 'limit_key' => 'maxResults', 'non_aggregate_keys' => ['registryId', 'repositoryName', 'imageId', 'imageScanStatus', 'imageScanFindings'], 'output_token' => 'nextToken', 'result_key' => 'imageScanFindings.findings'], 'DescribeImages' => ['input_token' => 'nextToken', 'limit_key' => 'maxResults', 'output_token' => 'nextToken', 'result_key' => 'imageDetails'], 'DescribeRepositories' => ['input_token' => 'nextToken', 'limit_key' => 'maxResults', 'output_token' => 'nextToken', 'result_key' => 'repositories'], 'GetLifecyclePolicyPreview' => ['input_token' => 'nextToken', 'limit_key' => 'maxResults', 'non_aggregate_keys' => ['registryId', 'repositoryName', 'lifecyclePolicyText', 'status', 'summary'], 'output_token' => 'nextToken', 'result_key' => 'previewResults'], 'ListImages' => ['input_token' => 'nextToken', 'limit_key' => 'maxResults', 'output_token' => 'nextToken', 'result_key' => 'imageIds']]];
