<?php

namespace SierraTecnologia\Cashier\Tests\Fixtures;

use SierraTecnologia\Cashier\Billable;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use Billable;
}
