<?php

namespace NF_FU_LIB;

// This file was auto-generated from sdk-root/src/data/monitoring/2010-08-01/waiters-2.json
return ['version' => 2, 'waiters' => ['AlarmExists' => ['delay' => 5, 'maxAttempts' => 40, 'operation' => 'DescribeAlarms', 'acceptors' => [['matcher' => 'path', 'expected' => \true, 'argument' => 'length(MetricAlarms[]) > `0`', 'state' => 'success']]]]];
