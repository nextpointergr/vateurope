<?php
namespace NextPointer\VatEurope\Facades;
use Illuminate\Support\Facades\Facade;

class VatEurope extends Facade {
    protected static function getFacadeAccessor() { return 'vat-europe'; }
}