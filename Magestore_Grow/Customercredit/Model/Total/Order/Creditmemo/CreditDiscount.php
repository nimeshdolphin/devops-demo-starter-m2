<?php
/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Customercredit
 * @copyright   Copyright (c) 2017 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 *
 */

namespace Magestore\Customercredit\Model\Total\Order\Creditmemo;

class CreditDiscount extends \Magento\Sales\Model\Order\Total\AbstractTotal
{

    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        if ($order->getCustomercreditDiscount() < 0.0001) {
            return;
        }
        $creditmemo->setBaseCustomercreditDiscount(0);
        $creditmemo->setCustomercreditDiscount(0);

        $totalDiscountAmount = 0;
        $baseTotalDiscountAmount = 0;

        $hiddenTaxDiscount = 0;
        $baseHiddenTaxDiscount = 0;

        $totalDiscountRefunded = 0;
        $baseTotalDiscountRefunded = 0;

        $hiddenTaxRefunded = 0;
        $baseHiddenTaxRefunded = 0;

        foreach ($order->getCreditmemosCollection() as $existedCreditmemo) {
            if ($existedCreditmemo->getCustomercreditDiscount()) {
                $baseTotalDiscountRefunded += $existedCreditmemo->getBaseCustomercreditDiscount();
                $totalDiscountRefunded += $existedCreditmemo->getCustomercreditDiscount();

                $baseHiddenTaxRefunded += $existedCreditmemo->getBaseCustomercreditHiddenTax();
                $hiddenTaxRefunded += $existedCreditmemo->getCustomercreditHiddenTax();
            }
        }

        $baseShippingAmount = $creditmemo->getBaseShippingAmount();
        if ($baseShippingAmount) {
            $baseTotalDiscountAmount += $baseShippingAmount * $order->getBaseCustomercreditDiscountForShipping() / $order->getBaseShippingAmount();
            $totalDiscountAmount += $order->getShippingAmount() * $baseTotalDiscountAmount / $order->getBaseShippingAmount();

            $baseHiddenTaxDiscount += $baseShippingAmount * $order->getBaseCustomercreditShippingHiddenTax() / $order->getBaseShippingAmount();
            $hiddenTaxDiscount += $order->getShippingAmount() * $baseHiddenTaxDiscount / $order->getBaseShippingAmount();
        }

        foreach ($creditmemo->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            if ($orderItem->isDummy())
                continue;

            $orderItemBaseDiscount = (float)$orderItem->getBaseCustomercreditDiscount();
            $orderItemDiscount = (float)$orderItem->getCustomercreditDiscount();

            $orderItemBaseHiddenTax = (float)$orderItem->getBaseCustomercreditHiddenTax();
            $orderItemHiddenTax = (float)$orderItem->getCustomercreditHiddenTax();

            $orderItemQty = $orderItem->getQtyOrdered();
            $refundItemQty = $item->getQty();
            if ($orderItemDiscount && $orderItemQty) {
                $totalDiscountAmount += $creditmemo->roundPrice($refundItemQty * $orderItemDiscount / $orderItemQty, 'regular', true);
                $baseTotalDiscountAmount += $creditmemo->roundPrice($refundItemQty * $orderItemBaseDiscount / $orderItemQty, 'base', true);

                $hiddenTaxDiscount += $creditmemo->roundPrice($refundItemQty * $orderItemHiddenTax / $orderItemQty, 'regular', true);
                $baseHiddenTaxDiscount += $creditmemo->roundPrice($refundItemQty * $orderItemBaseHiddenTax / $orderItemQty, 'base', true);

            }
        }
        $creditmemo->setBaseCustomercreditDiscount($baseTotalDiscountAmount);
        $creditmemo->setCustomercreditDiscount($totalDiscountAmount);

        $creditmemo->setBaseCustomercreditHiddenTax($baseHiddenTaxDiscount);
        $creditmemo->setCustomercreditHiddenTax($hiddenTaxDiscount);

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $hiddenTaxDiscount - $totalDiscountAmount);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseHiddenTaxDiscount - $baseTotalDiscountAmount);

        $creditmemo->setAllowZeroGrandTotal(true);
    }

    /**
     * check credit memo is last or not
     *
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @return boolean
     */
    public function isLast($creditmemo)
    {
        foreach ($creditmemo->getAllItems() as $item) {
            if (!$item->isLast()) {
                return false;
            }
        }
        return true;
    }
}