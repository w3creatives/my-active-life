<?php

namespace App\Repositories;

use App\Models\DataSourceProfile;

class DataSourceProfileRepository
{

    public function __construct() {}

    public function createOrUpdate($data)
    {
        DataSourceProfile::createOrUpdate($data, [
            'user_id' => $data['user_id'],
            'data_source_id' => $data['data_source_id']
        ]);
    }
}
