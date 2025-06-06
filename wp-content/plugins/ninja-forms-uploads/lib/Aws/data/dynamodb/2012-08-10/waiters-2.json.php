<?php

namespace NF_FU_LIB;

// This file was auto-generated from sdk-root/src/data/dynamodb/2012-08-10/waiters-2.json
return ['version' => 2, 'waiters' => ['TableExists' => ['delay' => 20, 'operation' => 'DescribeTable', 'maxAttempts' => 25, 'acceptors' => [['expected' => 'ACTIVE', 'matcher' => 'path', 'state' => 'success', 'argument' => 'Table.TableStatus'], ['expected' => 'ResourceNotFoundException', 'matcher' => 'error', 'state' => 'retry']]], 'TableNotExists' => ['delay' => 20, 'operation' => 'DescribeTable', 'maxAttempts' => 25, 'acceptors' => [['expected' => 'ResourceNotFoundException', 'matcher' => 'error', 'state' => 'success']]]]];
