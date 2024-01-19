<?php

use PauloHortelan\OltMonitoring\Facades\ZTE;
use PauloHortelan\OltMonitoring\Models\Olt;
use PauloHortelan\OltMonitoring\Services\ZTE\ZTEService;

uses()->group('ZTE-C300');

beforeEach(function () {
    $this->correctInterface = 'gpon-onu_1/9/4:4';
    $this->wrongInterface = 'gpon-onu_1/2/1:99';

    $this->correctSerial = 'CMSZ3B0BEA20';
    $this->wrongSerial = 'ALCLB40D2CC1';

    $this->olt = Olt::create([
        'name' => 'olt-test1',
        'host' => '127.0.0.101',
        'username' => 'user',
        'password' => 'pass1234',
        'brand' => 'ZTE',
        'model' => 'C300',
    ]);
});

// Create connection
it('can connect on telnet', function () {
    $zte = ZTE::connect($this->olt);

    expect($zte)->toBeInstanceOf(ZTEService::class);
})->skipIfFakeConnection();

// Optical power
it('can get ont optical power', function () {
    $opticalPower = ZTE::connect($this->olt)->ontOpticalPower($this->correctInterface);

    expect($opticalPower)->toBeFloat();
})->depends('it can connect on telnet');

it('throws exception when cannot get ont optical power', function () {
    ZTE::connect($this->olt)->ontOpticalPower($this->wrongInterface);
})->depends('it can connect on telnet')->throws(Exception::class);

// Interface
it('can get ont interface', function () {
    $interface = ZTE::connect($this->olt)->ontInterface($this->correctSerial);

    expect($interface)->toStartWith('gpon-onu');
})->depends('it can connect on telnet');

it('throws exception when cannot get ont interface', function () {
    ZTE::connect($this->olt)->ontInterface($this->wrongSerial);
})->depends('it can connect on telnet')
    ->throws(Exception::class);

// Close connection
it('can close connection', function () {
    $zte = ZTE::connect($this->olt)->ontOpticalPower($this->correctInterface);
    $zte->disconnect();
    
    $zte->ontOpticalPower($this->correctInterface);
})->depends(
    'it can get ont optical power',
    'it throws exception when cannot get ont optical power',
    'it can get ont interface',
    'it throws exception when cannot get ont interface'
)->throws(Error::class);
