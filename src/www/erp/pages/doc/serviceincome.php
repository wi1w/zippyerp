<?php

namespace ZippyERP\ERP\Pages\Doc;

use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use ZippyERP\ERP\Entity\Customer;
use ZippyERP\ERP\Entity\Doc\Document;
use ZippyERP\ERP\Entity\Item;
use ZippyERP\ERP\Helper as H;
use Zippy\WebApplication as App;

/**
 * Страница  ввода  акта  о  выполненных работах
 * сторонней организацией
 */
class ServiceIncome extends \ZippyERP\System\Pages\Base
{

    public $_itemlist = array();
    private $_doc;
    private $_rowid = 0;
    private $_basedocid = 0;

    public function __construct($docid = 0, $basedocid = 0)
    {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, "OnAutoContragent");
        $this->docform->add(new AutocompleteTextInput('contract'))->onText($this, "OnAutoContract");
        $this->docform->add(new CheckBox('isnds'))->onChange($this, 'onIsnds');
        $this->docform->add(new CheckBox('cash'));
        $this->docform->add(new CheckBox('prepayment'))->setChecked(true);
        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Label('totalnds'));
        $this->docform->add(new Label('total'));
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new AutocompleteTextInput('edititem'))->onText($this, "OnAutoItem");
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editpricends'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');
        //$this->editdetail->add(new SubmitLink('additem'))->onClick($this, 'addItemOnClick');

        if ($docid > 0) {    //загружаем   содержимок  документа настраницу
            $this->_doc = Document::load($docid);
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->contract->setKey($this->_doc->headerdata['contract']);
            $this->docform->contract->setText($this->_doc->headerdata['contractnumber']);

            $this->docform->isnds->setChecked($this->_doc->headerdata['isnds']);
            $this->docform->cash->setChecked($this->_doc->headerdata['cash']);
            $this->docform->prepayment->setChecked($this->_doc->headerdata['prepayment']);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->customer->setKey($this->_doc->headerdata['customer']);
            $this->docform->customer->setText($this->_doc->headerdata['customername']);

            foreach ($this->_doc->detaildata as $item) {
                $item = new Item($item);
                $this->_itemlist[$item->item_id] = $item;
            }
        } else {
            $this->_doc = Document::create('ServiceIncome');
            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    $this->docform->reference->setText($basedoc->meta_desc . " №" . $basedoc->document_number);


                    if ($basedoc->meta_name == 'PurchaseInvoice') {
                        $this->docform->isnds->setChecked($basedoc->headerdata['isnds']);
                        $this->docform->customer->setKey($basedoc->headerdata['customer']);
                        $this->docform->customer->setText($basedoc->headerdata['customername']);
                        $this->docform->contract->setKey($basedoc->headerdata['contract']);
                        $this->docform->contract->setText($basedoc->headerdata['contractnumber']);


                        foreach ($basedoc->detaildata as $_item) {
                            $item = new Item($_item);
                            //$item->price = $item->pricends;
                            $this->_itemlist[$item->item_id] = $item;
                        }
                    }
                }
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();

        $this->add(new \ZippyERP\ERP\Blocks\Item('itemdetail', $this, 'OnItem'))->setVisible(false);
    }

    public function detailOnRow($row)
    {
        $item = $row->getDataItem();

        $row->add(new Label('item', $item->itemname));
        $row->add(new Label('measure', $item->measure_name));
        $row->add(new Label('quantity', ($item->quantity / 1000)));
        $row->add(new Label('price', H::fm($item->price)));
        $row->add(new Label('pricends', H::fm($item->pricends)));
        $row->add(new Label('amount', H::fm(($item->quantity / 1000) * $item->price)));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function editOnClick($sender)
    {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText(($item->quantity / 1000));
        $this->editdetail->editprice->setText(H::fm($item->price));
        $this->editdetail->editpricends->setText(H::fm($item->pricends));
        $this->editdetail->edititem->setKey($item->item_id);
        $this->editdetail->edititem->setText($item->itemname);
        //  $this->editdetail->editid->setText($item->item_id);
        $this->_rowid = $item->item_id;
    }

    public function deleteOnClick($sender)
    {
        $item = $sender->owner->getDataItem();
        // unset($this->_itemlist[$item->item_id]);

        $this->_itemlist = array_diff_key($this->_itemlist, array($item->item_id => $this->_itemlist[$item->item_id]));
        $this->docform->detail->Reload();
    }

    public function addrowOnClick($sender)
    {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->_rowid = 0;
    }

    public function saverowOnClick($sender)
    {
        $id = $this->editdetail->edititem->getKey();
        if ($id == 0) {
            $this->setError("Не выбран товар");
            return;
        }
        $item = Item::load($id);
        $item->quantity = 1000 * $this->editdetail->editquantity->getText();
        $item->price = $this->editdetail->editprice->getText() * 100;
        $item->pricends = $this->editdetail->editpricends->getText() * 100;

        unset($this->_itemlist[$this->_rowid]);
        $this->_itemlist[$item->item_id] = $item;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();

        //очищаем  форму
        $this->editdetail->edititem->setKey(-1);
        $this->editdetail->edititem->setText('');
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editpricends->setText("");
        $this->editdetail->editprice->setText("");
    }

    public function cancelrowOnClick($sender)
    {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function savedocOnClick($sender)
    {
        if ($this->checkForm() == false) {
            return;
        }

        $this->calcTotal();

        $this->_doc->headerdata = array(
            'customer' => $this->docform->customer->getKey(),
            'customername' => $this->docform->customer->getText(),
            'contract' => $this->docform->contract->getKey(),
            'contractnumber' => $this->docform->contract->getText(),
            'isnds' => $this->docform->isnds->isChecked(),
            'cash' => $this->docform->cash->isChecked(),
            'prepayment' => $this->docform->prepayment->isChecked(),
            'totalnds' => $this->docform->totalnds->getText() * 100,
            'total' => $this->docform->total->getText() * 100
        );
        $this->_doc->detaildata = array();
        foreach ($this->_itemlist as $item) {
            $this->_doc->detaildata[] = $item->getData();
        }

        $this->_doc->amount = 100 * $this->docform->total->getText();
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = $this->docform->document_date->getDate();
        $isEdited = $this->_doc->document_id > 0;
        $this->_doc->datatag = $this->docform->customer->getKey();

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

            $this->_doc->save();

            if ($sender->id == 'execdoc') {
                $this->_doc->updateStatus(Document::STATE_EXECUTED);
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }

            if ($this->_basedocid > 0) {
                $this->_doc->AddConnectedDoc($this->_basedocid);
                $this->_basedocid = 0;
            }
            if ($this->docform->contract->getKey() > 0) {
                $this->_doc->AddConnectedDoc($this->docform->contract->getKey());
            }
            $conn->CommitTrans();
            App::RedirectBack();
        } catch (\ZippyERP\System\Exception $ee) {
            $conn->RollbackTrans();
            $this->setError($ee->getMessage());
        } catch (\Exception $ee) {
            $conn->RollbackTrans();
            throw new \Exception($ee->getMessage()s);
        }
    }

    /**
     * Расчет  итого
     *
     */
    private function calcTotal()
    {

        $total = 0;
        $totalnds = 0;
        foreach ($this->_itemlist as $item) {
            $item->amount = $item->pricends * ($item->quantity / 1000);
            $item->nds = $item->amount - $item->price * ($item->quantity / 1000);
            $total = $total + $item->amount;
            $totalnds = $totalnds + $item->nds;
        }
        $this->docform->total->setText(H::fm($total));
        $this->docform->totalnds->setText(H::fm($totalnds));
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm()
    {

        if (count($this->_itemlist) == 0) {
            $this->setError("Не введен ни один  товар");
        }
        if ($this->docform->customer->getKey() == 0) {
            $this->setError("Не выбран  исполнитель");
        }
        return !$this->isError();
    }

    public function beforeRender()
    {
        parent::beforeRender();
        $this->docform->totalnds->setVisible($this->docform->isnds->isChecked());
        $this->calcTotal();

        App::$app->getResponse()->addJavaScript("var _nds = " . H::nds() . ";var nds_ = " . H::nds(true) . ";");
    }

    public function onIsnds($sender)
    {
        foreach ($this->_itemlist as $item) {
            if ($sender->isChecked() == false) {
                $item->price = $item->pricends;
            } else {
                $item->price = $item->pricends - $item->pricends * H::nds(true);
            }
        }
        $this->docform->detail->Reload();
    }

    public function backtolistOnClick($sender)
    {
        App::RedirectBack();
    }

    public function addItemOnClick($sender)
    {
        $this->editdetail->setVisible(false);
        $this->itemdetail->open();
    }

    public function OnAutoContragent($sender)
    {
        $text = $sender->getValue();
        return Customer::findArray('customer_name', "customer_name like '%{$text}%' and ( cust_type=" . Customer::TYPE_SELLER . " or cust_type= " . Customer::TYPE_BUYER_SELLER . " )");
    }

    public function OnAutoContract($sender)
    {
        $text = $sender->getValue();
        return Document::findArray('document_number', "document_number like '%{$text}%' and ( meta_name='Contract' or meta_name='SupplierOrder' )");
    }

    public function OnAutoItem($sender)
    {
        $text = $sender->getValue();
        return Item::findArray('itemname', "itemname like'%{$text}%' and item_type =" . Item::ITEM_TYPE_SERVICE);
    }

}
