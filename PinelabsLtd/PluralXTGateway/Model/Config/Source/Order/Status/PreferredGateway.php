<?php
namespace PinelabsLtd\PluralXTGateway\Model\Config\Source\Order\Status;

use Magento\Framework\Option\ArrayInterface;
class PreferredGateway implements ArrayInterface
{

    /*
     * Option getter
     * @return array
     */
    public function toOptionArray()
    {
        $arr = $this->toArray();
        $ret = [];
        foreach ($arr as $key => $value) {
            $ret[] = [
                'value' => $key,
                'label' => $value
            ];
        }
        return $ret;
    }

    /*
     * Get options in "key-value" format
     * @return array
     */
    public function toArray()
    {
        $choose = [
                    "NONE"                  => "Select",
                    "AMEX"                  => "AMEX",
                    "AMEX_ENHANCED"         => "AMEX_ENHANCED",
                    "AXIS"                  => "AXIS",
                    "AXISB24"               => "AXISB24",
                    "BANKTEK"               => "BANKTEK",
                    "BFL"                   => "BFL",
                    "BHARATQR_HDFC"         => "BHARATQR_HDFC",
                    "BILLDESK"              => "BILLDESK",
                    "BOB"                   => "BOB",
                    "CCAVENUE_NET_BANKING"  => "CCAVENUE_NET_BANKING",
                    "CITI"                  => "CITI",
                    "CITRUS_NET_BANKING"    => "CITRUS_NET_BANKING",
                    "CORP"                  => "CORP",
                    "DEBIT_PIN_FSS"         => "DEBIT_PIN_FSS",
                    "EBS_NETBANKING"        => "EBS_NETBANKING",
                    "EDGE"                  => "EDGE",
                    "FEDERAL"               => "FEDERAL",
                    "FSS_NETBANKING"        => "FSS_NETBANKING",
                    "HDFC"                  => "HDFC",
                    "HDFC_DEBIT_EMI"        => "HDFC_DEBIT_EMI",
                    "HDFC_PRIZM"            => "HDFC_PRIZM",
                    "HSBC"                  => "HSBC",
                    "ICICI"                 => "ICICI",
                    "ICICI_SHAKTI"          => "ICICI_SHAKTI",
                    "IDBI"                  => "IDBI",
                    "LVB"                   => "LVB",
                    "MASHREQ"               => "MASHREQ",
                    "OPUS"                  => "OPUS",
                    "PAYTM"                 => "PAYTM",
                    "PayU"                  => "PayU",
                    "RAZOR_PAY"             => "RAZOR_PAY",
                    "SBI"                   => "SBI",
                    "SBI87"                 => "SBI87",
                    "SI_HDFC"               => "SI_HDFC",
                    "SI_PAYNIMO"            => "SI_PAYNIMO",
                    "UBI"                   => "UBI",
                    "UPI_AXIS"              => "UPI_AXIS",
                    "UPI_HDFC"              => "UPI_HDFC",
                    "WALLET_PAYZAPP"        => "WALLET_PAYZAPP",
                    "WALLET_PHONEPE"        => "WALLET_PHONEPE",
                    "YES"                   => "YES",
                    "ZEST_MONEY"            => "ZEST_MONEY"
        ];

        return $choose;
    }
}
?>