<?php

namespace Hyqo\Http\Pool;

class FilePool extends Pool
{
    public function __construct(array $storage = [])
    {
        parent::__construct(array_map([$this, 'convertFileData'], $storage));
    }

    protected function convertFileData(array $data): array
    {
        if (!\is_array($data['name'])) {
            return [$data];
        }

        $files = [];

        foreach ($data['name'] as $key => $name) {
            $files[$key] = [
                'name' => $name,
                'type' => $data['type'][$key],
                'tmp_name' => $data['tmp_name'][$key],
                'error' => $data['error'][$key],
                'size' => $data['size'][$key],
            ];
        }

        return $files;
    }

}
