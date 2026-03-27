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
                // Χρησιμοποιούμε τη βελτιωμένη parseAddress
                $parsed = $this->parseAddress($result->address);

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

    /**
     * Έξυπνος διαχωρισμός διεύθυνσης για VIES
     */
    private function parseAddress(string $address): array
    {
        // Αντικατάσταση πολλαπλών κενών και καθαρισμός
        $address = trim($address);
        $lines = explode("\n", str_replace("\r", "", $address));
        $lines = array_values(array_filter(array_map('trim', $lines)));

        $data = [
            'street' => '',
            'zip' => '',
            'city' => ''
        ];

        if (count($lines) >= 2) {
            // Συνήθως η 1η γραμμή είναι οδός και αριθμός
            $data['street'] = $lines[0];

            // Η τελευταία γραμμή συνήθως έχει ΤΚ και Πόλη (π.χ. "3060 ΛΕΜΕΣΟΣ")
            $lastLine = $lines[count($lines) - 1];

            // Regex για ΤΚ (4 έως 6 ψηφία) στην αρχή της τελευταίας γραμμής
            if (preg_match('/^(\d{4,6})\s+(.+)$/', $lastLine, $matches)) {
                $data['zip'] = $matches[1];
                $data['city'] = trim($matches[2]);
            } else {
                $data['city'] = $lastLine;
            }
        } else {
            // Αν είναι όλα σε μία γραμμή, προσπάθησε με regex
            if (preg_match('/^(.+?)\s+(\d{4,6})\s+(.+)$/', $address, $matches)) {
                $data['street'] = trim($matches[1]);
                $data['zip'] = $matches[2];
                $data['city'] = trim($matches[3]);
            } else {
                $data['street'] = $address;
            }
        }

        return $data;
    }
}
