<?php

namespace NF_FU_LIB;

// This file was auto-generated from sdk-root/src/data/cloudfront/2015-07-27/waiters-2.json
return ['version' => 2, 'waiters' => ['DistributionDeployed' => ['acceptors' => [['argument' => 'Distribution.Status', 'expected' => 'Deployed', 'matcher' => 'path', 'state' => 'success']], 'delay' => 60, 'description' => 'Wait until a distribution is deployed.', 'maxAttempts' => 25, 'operation' => 'GetDistribution'], 'InvalidationCompleted' => ['acceptors' => [['argument' => 'Invalidation.Status', 'expected' => 'Completed', 'matcher' => 'path', 'state' => 'success']], 'delay' => 20, 'description' => 'Wait until an invalidation has completed.', 'maxAttempts' => 30, 'operation' => 'GetInvalidation'], 'StreamingDistributionDeployed' => ['acceptors' => [['argument' => 'StreamingDistribution.Status', 'expected' => 'Deployed', 'matcher' => 'path', 'state' => 'success']], 'delay' => 60, 'description' => 'Wait until a streaming distribution is deployed.', 'maxAttempts' => 25, 'operation' => 'GetStreamingDistribution']]];
