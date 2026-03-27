<?php

namespace NextPointer\VatEurope\Services;

use NextPointer\VatEurope\Responses\VatResponse;

class VatEuropeService
{
    protected string $wsdl = "https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl";

    public function validate(string $countryCode, string $vatNumber): VatResponse
    {
        $countryCode = strtoupper(trim($countryCode));
        if ($countryCode === 'GR') $countryCode = 'EL';

        $vatNumber = preg_replace('/[^a-zA-Z0-9]/', '', $vatNumber);

        try {
            $context = stream_context_create([
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                'http' => ['user_agent' => 'PHPSoapClient']
            ]);

            $client = new \SoapClient($this->wsdl, [
                'stream_context' => $context,
                'connection_timeout' => config('vat-europe.timeout', 15),
                'exceptions' => true
            ]);

            $result = $client->checkVat(['countryCode' => $countryCode, 'vatNumber' => $vatNumber]);

            if ($result->valid) {
                $parsed = $this->splitAddress($result->address);
                return new VatResponse(true, [
                    'vat' => $result->vatNumber,
                    'name' => trim($result->name),
                    'street' => $parsed['street'],
                    'zip' => $parsed['zip'],
                    'city' => $parsed['city'],
                    'full_address' => trim(str_replace(["\n", "\r"], ' ', $result->address)),
                    'country_code' => $result->countryCode,
                ]);
            }
            return new VatResponse(false, [], 'Invalid VAT number.');
        } catch (\Exception $e) {
            return new VatResponse(false, [], 'Connection Error: ' . $e->getMessage());
        }
    }

    private function splitAddress(string $address): array
    {
        $clean = preg_replace('/\s+/', ' ', trim($address));
        $data = ['street' => $clean, 'zip' => '', 'city' => ''];
        if (preg_match('/(\d{5,6})\s*[-\s]\s*(.+)$/', $clean, $matches)) {
            $data['zip'] = $matches[1];
            $data['city'] = trim($matches[2]);
            $data['street'] = trim(str_replace($matches[0], '', $clean));
        }
        return $data;
    }
}