<?php

namespace Heritage\Foundation;

use Heritage\Container\Container;
use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

class UgaritCloudJsonFormatter extends JsonFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function normalizeRecord(LogRecord $record): array
    {
        $normalized = parent::normalizeRecord($record);

        $app = Container::getInstance();

        if ($app->bound('request')) {
            $requestId = $app->make('request')->header('Cloud-Request-ID');

            if ($requestId !== null) {
                $normalized['cloud_request_id'] = $requestId;
            }
        }

        return $normalized;
    }
}
