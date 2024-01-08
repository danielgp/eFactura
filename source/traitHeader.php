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

trait traitHeader
{

    use traitCompanies,
        traitTax;

    private function getHeader(array $arrayParameters): array {
        $arrayCBC      = explode(':', $arrayParameters['DocumentNameSpaces']['cbc']);
        $strCBC        = $arrayCBC[count($arrayCBC) - 1]; // CommonBasicComponents
        $strCAC        = $arrayParameters['cacName']; // CommonAggregateComponents
        $arrayDocument = [
            $strCBC => $this->getHeaderCommonBasicComponents($arrayParameters['Type'], $arrayParameters['CBC']),
            $strCAC => [
                'AccountingCustomerParty' => $this->getAccountingCustomerParty($arrayParameters['CAC']
                    ->AccountingCustomerParty->children('cac', true)->Party),
                'AccountingSupplierParty' => $this->getAccountingSupplierParty($arrayParameters['CAC']
                    ->AccountingSupplierParty->children('cac', true)->Party),
                'LegalMonetaryTotal'      => $this->getLegalMonetaryTotal($arrayParameters['CAC']->LegalMonetaryTotal),
                'TaxTotal'                => $this->getTaxTotal($arrayParameters['CAC']->TaxTotal),
            ],
        ];
        $intLineNo     = 0;
        if (isset($arrayParameters['CAC']->PaymentMeans)) {
            foreach ($arrayParameters['CAC']->PaymentMeans as $child) {
                $intLineNo++;
                $intLineStr                                          = ($intLineNo < 10 ? '0' : '')
                    . $intLineNo;
                $arrayDocument[$strCAC]['PaymentMeans'][$intLineStr] = $this->getPaymentMeans($child);
            }
        }
        // optional components =========================================================================================
        if (isset($arrayParameters['CAC']->InvoicePeriod)) {
            $arrayDocument[$strCAC]['InvoicePeriod'] = $this->getLegalInvoicePeriod($arrayParameters['CAC']
                ->InvoicePeriod);
        }
        return $arrayDocument;
    }

    private function getHeaderCommonBasicComponents(string $strType, $objCommonBasicComponents): array {
        $arrayOutput = [
            'CustomizationID'      => $objCommonBasicComponents->CustomizationID->__toString(),
            'DocumentCurrencyCode' => $objCommonBasicComponents->DocumentCurrencyCode->__toString(),
            'ID'                   => $objCommonBasicComponents->ID->__toString(),
            'IssueDate'            => $objCommonBasicComponents->IssueDate->__toString(),
        ];
        if (isset($objCommonBasicComponents->DueDate)) {
            $arrayOutput['DueDate'] = $objCommonBasicComponents->DueDate->__toString();
        }
        if (isset($objCommonBasicComponents->Note)) {
            $arrayOutput['Note'] = $objCommonBasicComponents->Note->__toString();
        }
        if (isset($objCommonBasicComponents->TaxPointDate)) {
            $arrayOutput['TaxPointDate'] = $objCommonBasicComponents->TaxPointDate->__toString();
        }
        if (isset($objCommonBasicComponents->UBLVersionID)) {
            $arrayOutput['UBLVersionID'] = $objCommonBasicComponents->UBLVersionID->__toString();
        }
        return array_merge($arrayOutput, $this->getHeaderTypeCode($strType, $objCommonBasicComponents));
    }

    private function getHeaderTypeCode(string $strType, $objCommonBasicComponents) {
        $arrayOutput = [];
        switch ($strType) {
            case 'CreditNote':
                $arrayOutput['CreditNoteTypeCode'] = (integer) $objCommonBasicComponents
                    ->CreditNoteTypeCode->__toString();
                break;
            case 'Invoice':
                $arrayOutput['InvoiceTypeCode']    = (integer) $objCommonBasicComponents
                    ->InvoiceTypeCode->__toString();
                break;
        }
        return $arrayOutput;
    }

    private function getLegalInvoicePeriod($child2): array {
        $arrayOutput = [];
        if (isset($child2->children('cbc', true)->StartDate)) {
            $arrayOutput['StartDate'] = $child2->children('cbc', true)->StartDate->__toString();
        }
        if (isset($child2->children('cbc', true)->EndDate)) {
            $arrayOutput['EndDate'] = $child2->children('cbc', true)->EndDate->__toString();
        }
        return $arrayOutput;
    }

    private function getLegalMonetaryTotal($child2): array {
        $objCBC      = $child2->children('cbc', true);
        $arrayOutput = [
            'LineExtensionAmount' => $this->getTagWithCurrencyParameter($objCBC->LineExtensionAmount),
            'TaxExclusiveAmount'  => $this->getTagWithCurrencyParameter($objCBC->TaxExclusiveAmount),
            'TaxInclusiveAmount'  => $this->getTagWithCurrencyParameter($objCBC->TaxInclusiveAmount),
            'PayableAmount'       => $this->getTagWithCurrencyParameter($objCBC->PayableAmount),
        ];
        // optional components =========================================================================================
        if (isset($child2->children('cbc', true)->AllowanceTotalAmount)) {
            $arrayOutput['AllowanceTotalAmount'] = $this->getTagWithCurrencyParameter($objCBC->AllowanceTotalAmount);
        }
        if (isset($child2->children('cbc', true)->ChargeTotalAmount)) {
            $arrayOutput['ChargeTotalAmount'] = $this->getTagWithCurrencyParameter($objCBC->ChargeTotalAmount);
        }
        if (isset($child2->children('cbc', true)->PrepaidAmount)) {
            $arrayOutput['PrepaidAmount'] = $this->getTagWithCurrencyParameter($objCBC->PrepaidAmount);
        }
        return $arrayOutput;
    }
}
