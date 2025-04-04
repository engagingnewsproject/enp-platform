<?php

namespace NF_FU_LIB;

// This file was auto-generated from sdk-root/src/data/rekognition/2016-06-27/waiters-2.json
return ['version' => 2, 'waiters' => ['ProjectVersionTrainingCompleted' => ['description' => 'Wait until the ProjectVersion training completes.', 'operation' => 'DescribeProjectVersions', 'delay' => 120, 'maxAttempts' => 360, 'acceptors' => [['state' => 'success', 'matcher' => 'pathAll', 'argument' => 'ProjectVersionDescriptions[].Status', 'expected' => 'TRAINING_COMPLETED'], ['state' => 'failure', 'matcher' => 'pathAny', 'argument' => 'ProjectVersionDescriptions[].Status', 'expected' => 'TRAINING_FAILED']]], 'ProjectVersionRunning' => ['description' => 'Wait until the ProjectVersion is running.', 'delay' => 30, 'maxAttempts' => 40, 'operation' => 'DescribeProjectVersions', 'acceptors' => [['state' => 'success', 'matcher' => 'pathAll', 'argument' => 'ProjectVersionDescriptions[].Status', 'expected' => 'RUNNING'], ['state' => 'failure', 'matcher' => 'pathAny', 'argument' => 'ProjectVersionDescriptions[].Status', 'expected' => 'FAILED']]]]];
