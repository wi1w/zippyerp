<html>
<body>
<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <th width="30">&nbsp;</th>
        <th width="100">&nbsp;</th>
        <th width="130">&nbsp;</th>
        <th width="50">&nbsp;</th>
        <th width="50">&nbsp;</th>
        <th width="60">&nbsp;</th>
        <th width="80">&nbsp;</th>
    </tr>


    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="7" valign="middle">
            <br><br>Оприходование излишков № {{document_number}} от {{date}} <br><br>
        </td>
    </tr>
    <tr>
        <td style="font-weight: bolder; ;" align colspan="7" valign="middle">
            Склад: {{storename}}
            <br></td>
    </tr>
    <tr>
        <td style="font-weight: bolder; ;" align colspan="7" valign="middle">
            Тип доходов: {{gainsname}}
            <br><br></td>
    </tr>

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th colspan="2" width="230" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Наименование</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="50">Ед.изм</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="50">Кол.</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Цена</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="80">Сумма</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{no}}</td>
        <td colspan="2">{{tovar_name}}</td>
        <td>{{measure}}</td>
        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}


</table>
<br> <br>
</body>
</html>
