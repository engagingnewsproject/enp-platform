<?php

namespace NF_FU_LIB;

// This file was auto-generated from sdk-root/src/data/rds/2014-10-31/waiters-1.json
return ['waiters' => ['__default__' => ['interval' => 30, 'max_attempts' => 60], '__DBInstanceState' => ['operation' => 'DescribeDBInstances', 'acceptor_path' => 'DBInstances[].DBInstanceStatus', 'acceptor_type' => 'output'], 'DBInstanceAvailable' => ['extends' => '__DBInstanceState', 'success_value' => 'available', 'failure_value' => ['deleted', 'deleting', 'failed', 'incompatible-restore', 'incompatible-parameters', 'incompatible-parameters', 'incompatible-restore']], 'DBInstanceDeleted' => ['extends' => '__DBInstanceState', 'success_value' => 'deleted', 'failure_value' => ['creating', 'modifying', 'rebooting', 'resetting-master-credentials']]]];
