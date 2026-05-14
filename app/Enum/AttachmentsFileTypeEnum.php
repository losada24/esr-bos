<?php

namespace App\Enum;

enum AttachmentsFileTypeEnum: string
{
    case WORKER_COMPENSATION_ATTACH = 'worker_compensation_attach';
    case WORKER_COMPENSATION_EXCEPTION_ATTACH = 'worker_compensation_exception_attach';
    case LIABILITY_EXPIRATION_ATTACH = 'liability_expiration_attach';
    case ORDER_FILES = 'order_files';
    case WALK_TROUGH_ATTACH = 'walk_trough_attach';
    case PRE_INSPECTION_ATTACH = 'pre_inspection_attach';
}
