<?php

namespace ZippyERP\ERP\Entity\Doc;

use ZippyERP\ERP\Entity\Entry;
use ZippyERP\ERP\Entity\SubConto;
use ZippyERP\ERP\Entity\MoneyFund;
use ZippyERP\ERP\Helper as H;

/**
 * Класс-сущность  локумент акт  о  выполненных работах
 *
 *
 */
class ServiceAct extends Document
{

    public function generateReport()
    {

        $i = 1;
        $total = 0;
        $detail = array();
        foreach ($this->detaildata as $value) {
            $detail[] = array("no" => $i++,
                "itemname" => $value['itemname'],
                "measure" => $value['measure_name'],
                "quantity" => $value['quantity'] / 1000,
                "price" => H::fm($value['price']),
                "pricends" => H::fm($value['pricends']),
                "amount" => H::fm(($value['quantity'] / 1000) * $value['price'])
            );
            $total += ($value['quantity'] / 1000) * $value['price'];
        }

        $header = array('date' => date('d.m.Y', $this->document_date),
            "customer" => $this->headerdata["customername"],
            "document_number" => $this->document_number,
            "nds" => H::fm($this->headerdata["nds"]),
            "totalnds" => $this->headerdata["totalnds"] > 0 ? H::fm($this->headerdata["totalnds"]) : 0,
            "total" => H::fm($this->headerdata["total"])
        );
        $report = new \ZippyERP\ERP\Report('serviceact.tpl');

        $html = $report->generate($header, $detail);

        return $html;
    }

    public function Execute()
    {
        $conn = \ZDB\DB::getConnect();
        $conn->StartTrans();

        $total = $this->headerdata['total'];
        $customer_id = $this->headerdata["customer"];


        if ($this->headerdata['cash'] == true) {

            $cash = MoneyFund::getCash();
            \ZippyERP\ERP\Entity\Entry::AddEntry("30", "36", $total, $this->document_id, $this->document_date);
            $sc = new SubConto($this, 36, 0 - $total);
            $sc->setCustomer($this->headerdata["customer"]);

            $sc->save();
            $sc = new SubConto($this, 30, $total);
            $sc->setMoneyfund($cash->id);

            // $sc->save();
        }
        if ($this->headerdata['prepayment'] == 1) {  //предоплата
            Entry::AddEntry("681", "36", $this->headerdata["total"], $this->document_id, $this->document_date);
            $sc = new SubConto($this, 36, 0 - $this->headerdata["total"]);
            $sc->setCustomer($this->headerdata["customer"]);
            $sc->save();
            $sc = new SubConto($this, 681, $this->headerdata["total"]);
            $sc->setCustomer($this->headerdata["customer"]);
            $sc->save();
            if ($this->headerdata['isnds'] > 0) {
                Entry::AddEntry("703", "643", $this->headerdata["totalnds"], $this->document_id, $this->document_date);
                $sc = new SubConto($this, 36, 0 - $this->headerdata['totalnds']);

                $sc->save();
            }
        }


        Entry::AddEntry("36", "703", $total, $this->document_id, $this->document_date);
        $sc = new SubConto($this, 36, $total);
        $sc->setCustomer($this->headerdata["customer"]);

        $sc->save();


        $conn->CompleteTrans();


        return true;
    }

    public function getRelationBased()
    {
        $list = array();
        $list['TaxInvoice'] = 'Налоговая накладная';

        return $list;
    }

}
