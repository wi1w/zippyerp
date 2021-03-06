<?php

namespace ZippyERP\ERP\Entity\Doc;

use ZippyERP\ERP\Entity\Entry;
use ZippyERP\ERP\Entity\SubConto;
use ZippyERP\ERP\Entity\MoneyFund;
use ZippyERP\ERP\Helper as H;

/**
 * Класс-сущность  документ возврат поставщику
 *
 */
class ReturnGoodsReceipt extends Document
{

    public function generateReport()
    {

        //$customer = \ZippyERP\ERP\Entity\Customer::load($this->headerdata["customer"]);

        $i = 1;

        $detail = array();
        foreach ($this->detaildata as $value) {
            $detail[] = array("no" => $i++,
                "itemname" => $value['itemname'],
                "measure" => $value['measure_name'],
                "quantity" => $value['quantity'] / 1000,
                "price" => H::fm($value['price']),
                "pricends" => H::fm($value['pricends']),
                "amount" => H::fm($value['amount'])
            );
        }
        $firm = \ZippyERP\System\System::getOptions("firmdetail");

        $header = array('date' => date('d.m.Y', $this->document_date),
            "firmname" => $firm['name'],
            "firmcode" => $firm['code'],
            "customername" => $this->headerdata["customername"],
            "document_number" => $this->document_number,
            "totalnds" => $this->headerdata["totalnds"] > 0 ? H::fm($this->headerdata["totalnds"]) : 0,
            "total" => H::fm($this->headerdata["total"])
        );


        $report = new \ZippyERP\ERP\Report('returngoodsreceipt.tpl');

        $html = $report->generate($header, $detail);

        return $html;
    }

    public function Execute()
    {
        $types = array();
        //аналитика
        foreach ($this->detaildata as $item) {
            $stock = \ZippyERP\ERP\Entity\Stock::getStock($this->headerdata['store'], $item['item_id'], $item['price'], true);

            $sc = new SubConto($this, $item['type'], 0 - ($item['amount'] - $item['nds']));
            $sc->setStock($stock->stock_id);
            $sc->setQuantity(0 - $item['quantity']);

            $sc->save();

            //группируем по синтетическим счетам
            if ($types[$item['type']] > 0) {
                $types[$item['type']] = $types[$item['type']] + $item['amount'] - $item['nds'];
            } else {
                $types[$item['type']] = $item['amount'] - $item['nds'];
            }
        }

        foreach ($types as $acc => $value) {
            Entry::AddEntry($acc, "63", 0 - $value, $this->document_id, $this->document_date);
            $sc = new SubConto($this, 63, $value);
            $sc->setCustomer($this->headerdata["customer"]);

            $sc->save();
        }

        $total = $this->headerdata['total'];

        if ($this->headerdata['cash'] == true) {

            $cash = MoneyFund::getCash();
            Entry::AddEntry("63", "30", 0 - $total, $this->document_id, $this->document_date);
            $sc = new SubConto($this, 63, 0 - $total);
            $sc->setCustomer($this->headerdata["customer"]);

            $sc->save();
            $sc = new SubConto($this, 30, $total);
            $sc->setMoneyfund($cash->id);

            // $sc->save();
        }

        //налоговый кредит
        if ($this->headerdata['totalnds'] > 0) {
            Entry::AddEntry("644", "63", 0 - $this->headerdata['totalnds'], $this->document_id, $this->document_date);
            $sc = new SubConto($this, 63, $this->headerdata['totalnds']);
            $sc->setCustomer($this->headerdata["customer"]);

            $sc->save();
            $sc = new SubConto($this, 644, 0 - $this->headerdata['totalnds']);
            $sc->setExtCode(\ZippyERP\ERP\Consts::TAX_NDS);

            //$sc->save();
        }


        return true;
    }

    public function getRelationBased()
    {
        $list = array();
        $list[''] = 'Корректировка (Додаток 2)';

        return $list;
    }

}
