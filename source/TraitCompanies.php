<?php

/**
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2024 Daniel Popiniuc
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

namespace danielgp\efactura;

trait TraitCompanies
{

    private function getAccountingCustomerParty($child2): array {
        $arrayMainOutput = [
            'PostalAddress'    => $this->getPostalAddress($child2->children('cac', true)->PostalAddress),
            'PartyLegalEntity' => $this->getPartyLegalEntity($child2->children('cac', true)->PartyLegalEntity),
        ];
        // optional components =========================================================================================
        if (isset($child2->children('cac', true)->PartyName)) {
            $arrayMainOutput['PartyName'] = $this->getPartyName($child2->children('cac', true)->PartyName);
        }
        if (isset($child2->children('cac', true)->PartyTaxScheme)) {
            $arrayMainOutput['PartyTaxScheme'] = $this->getPartyTaxScheme($child2
                    ->children('cac', true)->PartyTaxScheme);
        }
        if (isset($child2->children('cac', true)->PartyIdentification)) {
            $arrayMainOutput['PartyIdentification'] = $this->getPartyIdentification($child2
                    ->children('cac', true)->PartyIdentification);
        }
        if (isset($child2->children('cbc', true)->EndpointID)) {
            $arrayMainOutput['EndpointID'] = [
                'schemeID' => $child2->children('cbc', true)->EndpointID->attributes()->schemeID->__toString(),
                'value'    => $child2->children('cbc', true)->EndpointID->__toString(),
            ];
        }
        $arrayContact = $this->getContact($child2->children('cac', true)->Contact);
        if ($arrayContact != []) {
            $arrayMainOutput['Contact'] = $arrayContact;
        }
        return ['Party' => $arrayMainOutput];
    }

    private function getAccountingSupplierParty($child2): array {
        $arrayMainOutput = [
            'PostalAddress'    => $this->getPostalAddress($child2->children('cac', true)->PostalAddress),
            'PartyTaxScheme'   => $this->getPartyTaxScheme($child2->children('cac', true)->PartyTaxScheme),
            'PartyLegalEntity' => $this->getPartyLegalEntity($child2->children('cac', true)->PartyLegalEntity),
        ];
        if (isset($child2->children('cac', true)->PartyName)) {
            $arrayMainOutput['PartyName'] = $this->getPartyName($child2->children('cac', true)->PartyName);
        }
        // optional components =========================================================================================
        $arrayContact = $this->getContact($child2->children('cac', true)->Contact);
        if ($arrayContact != []) {
            $arrayMainOutput['Contact'] = $arrayContact;
        }
        if (isset($child2->children('cbc', true)->EndpointID)) {
            $arrayMainOutput['EndpointID'] = [
                'schemeID' => $child2->children('cbc', true)->EndpointID->attributes()->schemeID->__toString(),
                'value'    => $child2->children('cbc', true)->EndpointID->__toString(),
            ];
        }
        return ['Party' => $arrayMainOutput];
    }

    private function getContact($child3): array {
        // optional components =========================================================================================
        $arrayOutput = [];
        if (isset($child3->children('cbc', true)->Name)) {
            $arrayOutput['Name'] = $child3->children('cbc', true)->Name->__toString();
        }
        if (isset($child3->children('cbc', true)->Telephone)) {
            $arrayOutput['Telephone'] = $child3->children('cbc', true)->Telephone->__toString();
        }
        if (isset($child3->children('cbc', true)->ElectronicMail)) {
            $arrayOutput['ElectronicMail'] = $child3->children('cbc', true)->ElectronicMail->__toString();
        }
        return $arrayOutput;
    }

    private function getPartyIdentification($child3): array {
        return [
            'ID' => $child3->children('cbc', true)->ID->__toString(),
        ];
    }

    private function getPartyLegalEntity($child3): array {
        $arrayOutput = [
            'RegistrationName' => $child3->children('cbc', true)->RegistrationName->__toString(),
        ];
        // optional components =========================================================================================
        if (isset($child3->children('cbc', true)->CompanyID)) {
            $arrayOutput['CompanyID'] = $child3->children('cbc', true)->CompanyID->__toString();
        }
        if (isset($child3->children('cbc', true)->CompanyLegalForm)) {
            $arrayOutput['CompanyLegalForm'] = $child3->children('cbc', true)->CompanyLegalForm->__toString();
        }
        return $arrayOutput;
    }

    private function getPartyName($child3): array {
        $arrayOutput         = [];
        $arrayOutput['Name'] = $child3->children('cbc', true)->Name->__toString();
        return $arrayOutput;
    }

    private function getPartyTaxScheme($child3): array {
        return [
            'CompanyID' => $child3->children('cbc', true)->CompanyID->__toString(),
            'TaxScheme' => [
                'ID' => $child3->children('cac', true)->TaxScheme->children('cbc', true)->ID->__toString(),
            ]
        ];
    }

    private function getPaymentMeans($child2): array {
        $arrayOutput                                = [
            'PaymentMeansCode' => $child2->children('cbc', true)->PaymentMeansCode->__toString(),
        ];
        $childPayeeFinancialAccount                 = $child2->children('cac', true)->PayeeFinancialAccount;
        $arrayOutput['PayeeFinancialAccount']['ID'] = $childPayeeFinancialAccount
                ->children('cbc', true)->ID->__toString();
        // optional components =========================================================================================
        if (isset($child2->children('cbc', true)->PaymentID)) {
            $arrayOutput['PaymentID'] = $child2->children('cbc', true)->PaymentID->__toString();
        }
        if (isset($childPayeeFinancialAccount->children('cbc', true)->Name)) {
            $arrayOutput['PayeeFinancialAccount']['Name'] = $childPayeeFinancialAccount
                    ->children('cbc', true)->Name->__toString();
        }
        if (isset($childPayeeFinancialAccount->children('cac', true)->FinancialInstitutionBranch)) {
            $arrayOutput['PayeeFinancialAccount']['FinancialInstitutionBranch']['ID'] = $childPayeeFinancialAccount
                    ->children('cac', true)->FinancialInstitutionBranch->children('cbc', true)->ID->__toString();
        }
        return $arrayOutput;
    }

    private function getPostalAddress($child3): array {
        $arrayOutput = [
            'CityName'         => $child3->children('cbc', true)->CityName->__toString(),
            'Country'          => [
                'IdentificationCode' => $child3->children('cac', true)->Country
                    ->children('cbc', true)->IdentificationCode->__toString(),
            ],
            'CountrySubentity' => $child3->children('cbc', true)->CountrySubentity->__toString(),
            'StreetName'       => $child3->children('cbc', true)->StreetName->__toString(),
        ];
        // optional component ==========================================================================================
        if (isset($child3->children('cbc', true)->PostalZone)) {
            $arrayOutput['PostalZone'] = $child3->children('cbc', true)->PostalZone->__toString();
        }
        return $arrayOutput;
    }
}
