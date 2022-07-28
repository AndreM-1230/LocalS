if (!String.prototype.padStart) {
    String.prototype.padStart = function padStart(targetLength,padString) {
        targetLength = targetLength>>0; //truncate if number or convert non-number to 0;
        padString = String((typeof padString !== 'undefined' ? padString : ' '));
        if (this.length > targetLength) {
            return String(this);
        }
        else {
            targetLength = targetLength-this.length;
            if (targetLength > padString.length) {
                padString += padString.repeat(targetLength/padString.length); //append to original to ensure we are longer than needed
            }
            return padString.slice(0,targetLength) + String(this);
        }
    };
}

$(document).ready(function () {

    $('#spravoch').keypress(function (e) {

        if (e.which == 13) {
            //e.preventDefault();
            //Если выбран способ вставки из буфера обмена
            if ($('input[type=radio]:checked').val() == 2) {
                $("#sprav_ul li").remove();
                var pos = 0;
                var text_val = $(this).val();

                var input_data = [0];
                while (true) {

                    var foundPos = text_val.indexOf("\n", pos);
                    if (foundPos == -1) break;
                    //формируем массив input_data, где будут храниться номера позиций найденных переносов строк
                    input_data.push(foundPos);

                    pos = foundPos + 1; // продолжить поиск со следующей
                }

                if (input_data.length > 1) {


                    for (var i = 0; i <= input_data.length - 1; i++) {
                        var val1 = $(this).val().slice(input_data[i], input_data[i + 1]);
                        if (val1.lastIndexOf(" ") == -1 && val1.lastIndexOf("\t") == -1) {
                            //Если не найдены пробельны символы, а такеж не обнаружены символы табуляции
                            var val2 = '';
                            var input = "<li><input type='text' style='width:20px;' placeholder='1' value='" + val2 + "'><input type='hidden'  name='hid' value='" + val1 + "' style='width:20px;'></li>";
                        } else {
                            //Если найдены пробелы
                            if (val1.lastIndexOf("\u0020") > -1) {
                                val2 = (val1.slice(0, val1.lastIndexOf("\u0020")).length > 6) ? val1.slice(val1.lastIndexOf("\u0020") + 1) : '';
                                var last_ind = val1.lastIndexOf("\u0020");
                                //var val2 = val1.slice(val1.lastIndexOf("\u0020")+1);
                            }
                            //если найдены табуляции
                            else if (val1.lastIndexOf("\t") > -1) {
                                val2 = (val1.slice(0, val1.lastIndexOf("\t")).length > 6) ? val1.slice(val1.lastIndexOf("\t") + 1) : '';
                                //var val2 = val1.slice(val1.lastIndexOf("\t")+1);
                                var last_ind = val1.lastIndexOf("\t");
                            }
                            if (val2) {

                                val1 = val1.slice(0, last_ind);
                            }
                            var input = "<li><input type='text' style='width:20px;' placeholder='1' value='" + val2 + "'><input type='hidden'  name='hid' value='" + val1 + "' style='width:20px;'></li>";

                        }


                        //$('#sprav_ul').append(input);

                    }
                }
            } else {//если выбран построчный способ вставки
                var input = "<li><input type='text' style='width:20px;' placeholder='1'><input type='hidden'  name='hid' value='" + $(this).val().slice($(this).val().lastIndexOf("\n") + 1) + "' style='width:20px;'></li>";


                //$('#sprav_ul').append(input);
                var last_input = $('#sprav_ul li :input');
                last_input.focus();
            }
        }
        $('#sprav_ul li :input').on('keypress', function (e) {
            if (e.which == 13) {
                e.preventDefault();
                $('#spravoch').val(function (index, value) {
                    return value + "\r\n";
                }).focus();
            }
        });
    });

    //Справочный запрос (если был клик по кнопке 'Отправить')
    $('#search_det').on('submit', function (e) {

        e.preventDefault();

        var res = [];
        var i = 0;

    if($('#sprav_ul li :input').is('input')) {

        $('#sprav_ul li :input').each(function (v_index, v_value) {
            //формируем массив из внесенных значений


            if (res[i]) {
                if (!$.trim($(this).val())) {
                    delete res[i];
                } else {
                    res[i] += $.trim($(this).val());
                }
            } else {
                if (!$(this).val()) {
                    res[i] = '1|';
                } else {
                    res[i] = $.trim($(this).val()) + '|';
                }
            }
            if (v_index % 2 != 0) {
                i++;
            }


        })
    }else{

        if($('#spravoch').val()){
            res[i] = '1|'+$('#spravoch').val().trim();

        }
    }
/*
    for(var index in res){
        document.write(res[index]+'<br>');
    }
*/
        if (res.length > 0) {
            let send = $('#spravoch').val().split("\n");;
            $.ajax({
                type: 'POST',
                url: URL_ + "ajax.php",
                data: {send},
                success: function (resp) {
                    resp = JSON.parse(resp);
                    if (typeof resp != 'string') {
                        $('#search_det').css({'display':'none'});
                        var detail = '';



                        detail += "<h3>Названия деталей</h3><form method='post'><select multiple class='form-control' name='action' size='12'><option value='rout'>Структура изделий</option><option value='wrongspec'>Некомплектность</option><option value='purchased'>Покупные изделия</option><option value='labor'>Трудоёмкость</option><option value='laborsep'>Трудоёмкость (каждое изделие отдельно)</option><option value='reentran'>Входимость</option><option value='comreentran'>Итоговая входимость</option><option value='apply'>Применяемость в открытых заказах</option><option value='dce'>Перечень ДСЕ</option><option value='require'>Требования на входящие</option><option value='compare'>Сравнить изделия</option><option value='mpnk'>MPNK</option></select><table class='table '><thead><th>Название детали:</th><th>ДСЕ:</th><th>Количество:</th></thead>";

                        var i=0;
                        for(var index in resp){
                            if(index == 'fake_det'){
                                for(key in resp[index]) {
                                    detail += '<tr><td><p class="text-danger">Отсуствует в базе</p></td><td><input class="form-control detail_code"  type="text" value="' + key + '" name="code_nf[]"></td><td><input class="form-control detail_qty"  type="text" value="' + resp[index][key] + '" size="3" name="qty_nf[]"></td></tr>';
                                }
                            }else{
                            i++;
                            detail+='<tr><td>';
                                if(resp[index].title){
                                    detail+=resp[index].title;
                                }else{
                                    detail+='<p class="text-warning">Наименование не найдено</p>';
                                }
                            detail+= '</td><td><input  name ="id[]" type="hidden" value="'+resp[index].id+'"><input class="form-control detail_code"  type="text" value="'+resp[index].code+'" name="code[]"></td><td><input class="form-control detail_qty"  type="text" value="' + resp[index].quantity + '" size="3" name="qty[]"></td></tr>';
                            }
                        }
                        detail += "<tr class='success'><td colspan=3>Всего деталей найдено: "+i+"</td></tr></table><input type='submit' class='btn btn-primary btn-sm' id='more' name='more' value='Далее'></form>";

                        $('#parsing_form').append("<div id='info_details'>"+detail+"</div>");



                    }else{
                        alert (resp);
                    }


                }

            });



        }




    })
    /*
    *Отслеживаем изменения в поле input кода деталей и блокируем кнопку отправить, подгружаем название профессии
     */
    $('#parsing_form').on('focus','.detail_code',function(e){
        /*$('#more').attr({'disabled':true});*/

        $(this).on('change',function(e){

            var elem = $(this).parent().prev();
            var elem2 = $(this);
            //alert($(this).val());
            $.ajax({
                type:'POST',
                url: URL_ + "ajax.php",
                data:{det_code: $(this).val()},
                success:function(resp){
                    resp = JSON.parse(resp);
                    if(typeof(resp) === 'string'){

                    }else{
                        elem.text(resp.title);
                        elem2.prev().val(resp.id);
                        $('#more').attr({'disabled':false});

                    }



                }

            });

        })


    })




    /*
     *Отслеживание событий при вводе кодов профессий рабочих
     */

    $('.proff').on('change', function (e) {

        if ($(this).val()) {
            var prof_code = parseInt($(this).val());

            try {

                if (!prof_code) {
                    throw new Error('Код профессии должен быть числом, введите еще раз');

                }

                var res = send_ajax(prof_code, 'worker_code', this);

            }
            catch (e) {
                alert(e);
            }

        }

    })

    function get_sections(){
        if ($(this).val() > 0 ) {

            var dep_id = $(this).val();

            var uchastok = $('#uchastok');
            uchastok.attr({'disabled': false});

            uchastok.load(
                URL_ + "ajax.php",
                {dep_id: dep_id}
            );


            $('#uchastokdiv').css({'display': 'block'});

        }else {
            $('#uchastokdiv').css({'display': 'none'});
            $('#uchastok').attr({'disabled': true});
            $('#brigadadiv').css({'display': 'none'});
        }

    }

    $('#ceh').on('change', get_sections);

    /*
     *Отслеживаем изменения выбора участка
     */
    $('#uchastok').on('change', function () {
        if ($(this).val() > 0) {
            var sec_id = $(this).val();


            $('#brigada').load(
                URL_ + "ajax.php",
                {sec_id: sec_id}
            );


            $('#brigadadiv').fadeIn(300);

        } else {
            $('#brigadadiv').css({'display': 'none'});
            $('#brigada_inputdiv').css({'display': 'none'});


        }


    })


    /*
     * Отслеживания изменения выбора бригады
     *
     */

    $('#brigada').on('change', function (e) {
        var val = $(this).val();

        if (val == 1) {
            $('#brigada_inputdiv').fadeIn(300);

        }
        else {
            $('#brigada_inputdiv').fadeOut(300);
        }


    })

    /*
    *Отслеживаем нажатие на кнопку
    */


})


function send_ajax(data, param, elem) {

    $.ajax({
        url: URL_ + "ajax.php",
        type: 'POST',
        data: {code: data, param: param},
        success: function (resp) {
            if (resp == false) {
                $(elem).val('');
                if ($('#profession_name')) {
                    $('#profession_name').remove();

                }
                if ($('#profession_name_smezh')) {
                    $('#profession_name_smezh').remove();
                }
                alert('Профессий по данному коду не найдено, попробуйте еще раз');
            } else {
                resp = JSON.parse(resp);

                if ($(elem).attr('name') == 'profession') {

                    if ($('#profession_name')) {
                        $('#profession_name').remove();

                    }

                    $(elem).after(" <div class='form-group' id='profession_name'><label for='profession_name'>Название профессии</label> <input name='profession_name' class='form-control' type='text' value='" + resp.title + "' disabled=true'><input name='profession_id'  type='hidden' value='" + resp.id + "'></div>");
                } else if ($(elem).attr('name') == 'profession_smezh') {
                    if ($('#profession_name_smezh')) {
                        $('#profession_name_smezh').remove();
                    }
                    $(elem).after(" <div class='form-group' id='profession_name_smezh'><label for='profession_name_smezh'>Название профессии</label> <input name='profession_name_smezh' class='form-control' type='text' value='" + resp.title + "' disabled=true><input name='profession_id_smezh'  type='hidden' value='" + resp.id + "'></div>");
                }
            }
        }
    })


}
//Функция показа доп. инофрмации в покупных изделиях
function show_det(elem){
    $(elem).next().fadeToggle(300);
}
function show_next_tr(elem){
    $(elem).parent().parent().parent().next().fadeToggle(300);
}

function get_xls(elem,tree){
    alert('sdc');
    alert(tree);
    document.write(tree);
}
function mark_comment(elem,param){

    var dis_id = $(elem).parent().parent().parent().parent().next().next().children().val();
    var comment = $(elem).prev().val();
    add_comment(dis_id,comment,elem,param);

}

function change_short(elem,param,total_qty,user_id){
    var element = $(elem);
    if(!param){
        if(element.attr('class') == 'btn btn-danger btn-xs fix-width'){

            var kol = (element.parent().parent().prev().prev().prev().text());


            var dis_id = element.parent().parent().next().next().children().val();
            var to_add =  '<span class="btn btn-success btn-xs fix-width" onclick="change_short(this,null,null,'+user_id+');">Готово</span> <a href="#" class="btn btn-success btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово: '+kol+'"/><span class="btn btn-success btn-xs" onclick="change_qty(this,null,'+user_id+')">Ок</span></li></ul>'; // change_qty(this,null,'+user_id+')
            var res = add_qty(dis_id,kol,user_id,element,to_add);//alert(res);


        }else{

            var dis_id = element.parent().parent().next().next().children().val();
            var to_add = '<span class="btn btn-danger btn-xs fix-width" onclick="change_short(this,null,null,'+user_id+');">Дефицит</span><a href="#" class="btn btn-danger btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово:0"/><span class="btn btn-success btn-xs" onclick="change_qty(this,null,'+user_id+')">Ок</span></li></ul>';
            add_qty(dis_id,0,user_id,element,to_add);
            //element.parent().html();

        }
    }else if(param==1){
        if(element.attr('class') == 'btn btn-danger btn-xs fix-width'){

            var kol = (element.parent().parent().prev().prev().text());
            //
            //element.parent().parent().next().text(kol);
            //var zamen = element.parent().parent().parent().next().find('div.btn_deff');
            //
            //zamen.each(function(index,el){
            //    el = $(el);
            //    var kol2 = el.parent().prev().text();
            //
            //    var dis_id = el.parent().next().next().children().val();
            //    var to_add = '<span class="btn btn-success btn-xs fix-width" onclick="change_short(this,2,'+total_qty+','+user_id+');">Готово</span> <a href="#" class="btn btn-success btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово: '+kol2+'"/><span class="btn btn-success btn-xs" onclick="change_qty(this)">Ок</span></li></ul>';
            //    if(kol2 !=  el.parent().next().text()){
            //        add_qty(dis_id,kol2,user_id,el,to_add);
            //    }
            //
            //   // el.parent().next().text(kol2);
            //    //el.html('<span class="btn btn-success btn-xs fix-width" onclick="change_short(this,2,'+total_qty+');">Готово</span> <a href="#" class="btn btn-success btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово: '+kol2+'"/><span class="btn btn-success btn-xs" onclick="change_qty(this)">Ок</span></li></ul>');
            //});
           /*
            zamen.each(function(index,el){
                el.html('<span class="btn btn-success btn-xs fix-width">Готово</span> <a href="#" class="btn btn-success btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово: '+kol+'"/><span class="btn btn-success btn-xs">Ок</span></li></ul>');
            })
            */

            //element.parent().html('<span class="btn btn-success btn-xs fix-width" onclick="change_short(this,1,'+total_qty+','+user_id+');">Готово</span> <a href="#" class="btn btn-success btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li>Готово: '+kol+'</li></ul>');
            var dis_id = element.parent().parent().parent().next().find('tr.tr_hid');
            //alert(zamen);
            var to_add = '<span class="btn btn-success btn-xs fix-width" onclick="change_short(this,1,'+total_qty+','+user_id+');">Готово</span> <a href="#" class="btn btn-success btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li>Готово: '+kol+'</li></ul>';
            add_qty(dis_id,kol,user_id,element,to_add);

        }else{
            //var zamen = element.parent().parent().parent().next().find('div.btn_deff');
            //zamen.each(function(index,el){
            //    el = $(el);
            //    var to_add ='<span class="btn btn-danger btn-xs fix-width" onclick="change_short(this,2,'+total_qty+','+user_id+');">Дефицит</span><a href="#" class="btn btn-danger btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово: 0"/><span class="btn btn-success btn-xs" onclick="change_qty(this)">Ок</span></li></ul>';
            //
            //    var dis_id = el.parent().next().next().children().val();
            //
            //    if(0 !=  el.parent().next().text()){
            //        add_qty(dis_id,0,user_id,el,to_add);
            //    }
            //    el.parent().next().text(0);
            //
            //
            //    //el.html('<span class="btn btn-danger btn-xs fix-width" onclick="change_short(this,2,'+total_qty+');">Дефицит</span><a href="#" class="btn btn-danger btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово: 0"/><span class="btn btn-success btn-xs" onclick="change_qty(this)">Ок</span></li></ul>');
            //});
            //element.parent().parent().next().text('0');
            //element.parent().html('<span class="btn btn-danger btn-xs fix-width" onclick="change_short(this,1,'+total_qty+','+user_id+');">Дефицит</span><a href="#" class="btn btn-danger btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li>Готово:0</li></ul>');
            var dis_id = element.parent().parent().parent().next().find('tr.tr_hid');
            //alert(zamen);
            var to_add = '<span class="btn btn-danger btn-xs fix-width" onclick="change_short(this,1,'+total_qty+','+user_id+');">Дефицит</span><a href="#" class="btn btn-danger btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово: 0"/><span class="btn btn-success btn-xs" onclick="change_qty(this,null,'+user_id+')">Ок</span></li></ul>';
            add_qty(dis_id,0,user_id,element,to_add);
        }

    }else if(param == 2){
        if(element.attr('class') == 'btn btn-danger btn-xs fix-width'){

            var kol = (element.parent().parent().prev().text());
            //element.parent().parent().next().text(kol);

            var dis_id = element.parent().parent().next().next().children().val();
            var to_add = '<span class="btn btn-success btn-xs fix-width" onclick="change_short(this,2,'+total_qty+','+user_id+');">Готово</span> <a href="#" class="btn btn-success btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово: '+kol+'"/><span class="btn btn-success btn-xs" onclick="change_qty(this,1,user_id)">Ок</span></li></ul>';
            add_qty(dis_id,kol,user_id,element,to_add,total_qty);

            //element.parent().parent().parent().parent().parent().parent().parent().prev().find('td.com_qty').text(sum);

            //check_qty(total_qty,element);

            //element.parent().html();



        }else{
            var kol = (element.parent().parent().next().text());
            //element.parent().parent().next().text('0');
            //var qtyr = element.parent().parent().parent().parent().find('td.qtyready');
            //var sum=0;
            //qtyr.each(function(index,el){
            //    sum+=parseInt($(el).text());
            //});
            var to_add ='<span class="btn btn-danger btn-xs fix-width" onclick="change_short(this,2,'+total_qty+','+user_id+');">Дефицит</span><a href="#" class="btn btn-danger btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово:0"/><span class="btn btn-success btn-xs" onclick="change_qty(this,1,user_id)">Ок</span></li></ul>';
            var dis_id = element.parent().parent().next().next().children().val();
            add_qty(dis_id,0,user_id,element,to_add,total_qty);

            //element.parent().parent().parent().parent().parent().parent().parent().prev().find('td.com_qty').text(sum);


            //check_qty(total_qty,element);

            //element.parent().html('<span class="btn btn-danger btn-xs fix-width" onclick="change_short(this,2,'+total_qty+');">Дефицит</span><a href="#" class="btn btn-danger btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово:0"/><span class="btn btn-success btn-xs" onclick="change_qty(this,1)">Ок</span></li></ul>');


        }

    }
}
function change_qty(element,param,user){
    var elem=$(element);
    var num = parseInt(elem.prev().val());
    if(!param){
        var num_tot = elem.parent().parent().parent().parent().prev().prev().prev().text();
        if(num>=0 && num<=num_tot){
            elem.parent().parent().parent().parent().next().text(num);
            var dis_id = elem.parent().parent().parent().parent().next().next().children().val();

            if(num == num_tot){
                var to_add = elem.parent().parent().parent().html('<span class="btn btn-success btn-xs fix-width" onclick="change_short(this,null,null,'+user+');">Готово</span> <a href="#" class="btn btn-success btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово: '+num+'"/><span class="btn btn-success btn-xs" onclick="change_qty(this,null,'+user+')">Ок</span></li></ul>');
            }else{
                var to_add = elem.parent().parent().parent().html('<span class="btn btn-danger btn-xs fix-width" onclick="change_short(this,null,null,'+user+');">Дефицит</span><a href="#" class="btn btn-danger btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово:0"/><span class="btn btn-success btn-xs" onclick="change_qty(this,null,'+user+')">Ок</span></li></ul>');
            }
            add_qty(dis_id,num,user, elem.parent().parent().prev().prev(),to_add);

        }else{
            elem.prev().val('');
            alert('Вы ввели число большее, чем максимальное количество, либо вы ввели не число, попробуйте еще раз!');
        }
    }else{

        var num_tot = elem.parent().parent().parent().parent().prev().text();
        if(num>=0 && num<=num_tot){
            //elem.parent().parent().parent().parent().next().text(num);
            //if(num == num_tot){
            //    var qtyr = elem.parent().parent().parent().parent().parent().parent().find('td.qtyready');
            //    var sum=0;
            //    qtyr.each(function(index,el){
            //        sum+=parseInt($(el).text());
            //    });
            //    var qty = elem.parent().parent().parent().parent().parent().parent().parent().parent().parent().prev().find('td.com_qty').text();
            //    elem.parent().parent().parent().parent().parent().parent().parent().parent().parent().prev().find('td.com_qty').text(parseInt(sum));
                var num_total_com = elem.parent().parent().parent().parent().parent().parent().parent().parent().parent().prev().find('td.qty').text();

                var dis_id = elem.parent().parent().parent().parent().next().next().children().val();
                //elem.parent().parent().parent().parent().parent().parent().parent().parent().prev().find('td.com_qty').text(parseInt(num)+parseInt(qty));
                if(num == num_tot){
                    var to_add ='<span class="btn btn-success btn-xs fix-width" onclick="change_short(this,2,null,'+user+');">Готово</span> <a href="#" class="btn btn-success btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово: '+num+'"/><span class="btn btn-success btn-xs" onclick="change_qty(this,1,'+user+')">Ок</span></li></ul>';
                }else{
                    var to_add = '<span class="btn btn-danger btn-xs fix-width" onclick="change_short(this,2,null,'+user+');">Дефицит</span><a href="#" class="btn btn-danger btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово:0"/><span class="btn btn-success btn-xs" onclick="change_qty(this,1,'+user+')">Ок</span></li></ul>';
                }


                add_qty(dis_id,num,user,elem.parent().parent().prev().prev(),to_add,num_total_com);
                //check_qty(num_total_com,elem.parent().parent(),user);
                //elem.parent().parent().parent().html('<span class="btn btn-success btn-xs fix-width" onclick="change_short(this,2);">Готово</span> <a href="#" class="btn btn-success btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово: '+num+'"/><span class="btn btn-success btn-xs" onclick="change_qty(this,1,'+user+')">Ок</span></li></ul>');
            //}else{
            //    var qtyr = elem.parent().parent().parent().parent().parent().parent().find('td.qtyready');
            //    var sum=0;
            //    qtyr.each(function(index,el){
            //        sum+=parseInt($(el).text());
            //    });
            //    var qty = elem.parent().parent().parent().parent().parent().parent().parent().parent().parent().prev().find('td.com_qty').text();
            //    elem.parent().parent().parent().parent().parent().parent().parent().parent().parent().prev().find('td.com_qty').text(parseInt(sum));
            //    var num_total_com = elem.parent().parent().parent().parent().parent().parent().parent().parent().parent().prev().find('td.qty').text();
            //
            //    var dis_id = elem.parent().parent().parent().parent().next().next().children().val();
            //
            //
            //    add_qty(dis_id,num,user,elem.parent().parent().prev().prev(),to_add,num_total_com);
                //check_qty(num_total_com,elem.parent().parent(),user);
                //elem.parent().parent().parent().html('<span class="btn btn-danger btn-xs fix-width" onclick="change_short(this,2);">Дефицит</span><a href="#" class="btn btn-danger btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово:0"/><span class="btn btn-success btn-xs" onclick="change_qty(this,1,'+user+')">Ок</span></li></ul>');
            //}

        }else{
            elem.prev().val('');
            alert('Вы ввели число большее, чем максимальное количество, либо вы ввели не число, попробуйте еще раз');
        }

    }
}
function check_qty(total_qty,elem,user){


    var qtyr = elem.parent().parent().parent().parent().parent().parent().parent().prev().find('td.com_qty').html();

    if(total_qty == qtyr){
        var el = elem.parent().parent().parent().parent().parent().parent().parent().prev().find('div.btn-group:first');
        el.html('<span class="btn btn-success btn-xs fix-width" onclick="change_short(this,1,'+total_qty+','+user+');">Готово</span> <a href="#" class="btn btn-success btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li>Готово: '+total_qty+'</li></ul>');
    }else{
        var el = elem.parent().parent().parent().parent().parent().parent().parent().prev();
        if(el.find('div.btn-group:first>span.btn-success')) {
            el.find('div.btn-group:first').html('<span class="btn btn-danger btn-xs fix-width" onclick="change_short(this,1,'+total_qty+','+user+');">Дефицит</span><a href="#" class="btn btn-danger btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li>Готово:'+qtyr+'</li></ul>');
        }
    }

}
//Добавление комментария
function add_comment(dis_id,comment,elem,param){

    var dis_id = parseInt(dis_id);

    if(dis_id>0){
        $.ajax({
            url: URL_ + 'ajax.php',

            data:{dis_id_comm:dis_id,text:comment},
            success:function(data){
                if(data != 1){
                    alert('Комментарий не был добавлен, произошла ошибка, обратитесь к разработчику!');
                }else{
                    if(comment){
                        $(elem).parent().parent().prev().children().css({'color':'#FF4500'});
                        if(param){
                            $(elem).parent().parent().parent().parent().parent().parent().parent().parent().parent().prev().find('span.glyphicon').css({'color':'#FF4500'})
                        }
                    }else{
                        $(elem).parent().parent().prev().children().css({'color':'#696969'});


                    }
                }
            },
            type:'POST',
            error:function(obj,err){
                alert('Произошла непредвиденная ошибка, не удалось добавить комментарий');
            }
        });
    }else{
        alert('Произошла ошибка: не удается найти указанное разузлование');
    }
}
//Добавление готового количества в базу
function add_qty(dis_id,qty,user,element,to_add,total_qty){
    if(typeof dis_id == 'object'){
        to_upd = '';
        dis_id.each(function(index,el){
            var value = "'"+$(el).children().children('input').val()+"'"+',';
            to_upd +=value;
        })


    }else{
        var to_upd = parseInt(dis_id);
    }
    var qty = parseInt(qty);

    var user = parseInt(user);
    //alert(to_upd);

    //alert(to_upd);alert(qty);alert(user);


    if(dis_id && qty>=0 && user){
        $.ajax({
            url: URL_ + 'ajax.php',
            data:{dis_id:to_upd,qtyr:qty,user:user},
            success:function(data){
                //alert(data);
                if(data == 1){
                    element.parent().parent().next().text(qty);

                    if(total_qty){//alert(total_qty);
                        //alert (123);
                        var qtyr = element.parent().parent().parent().parent().find('td.qtyready');
                        var sum=0;
                        qtyr.each(function(index,el){
                            sum+=parseInt($(el).text());
                        });
                        element.parent().parent().parent().parent().parent().parent().parent().prev().find('td.com_qty').text(sum);
                        check_qty(total_qty,element,user);
                    }


                    if(typeof to_upd === 'string'){

                         var total = element.parent().parent().prev().prev().text();

                        if(element.attr('class') == 'btn btn-danger btn-xs fix-width') {

                            var zamen = element.parent().parent().parent().next().find('div.btn_deff');

                            zamen.each(function (index, el) {
                                el = $(el);
                                var kol2 = el.parent().prev().text();
                                var dis_id = el.parent().next().next().children().val();
                                el.parent().next().text(kol2);
                                el.html('<span class="btn btn-success btn-xs fix-width" onclick="change_short(this,2,' + total + ',' + user + ');">Готово</span> <a href="#" class="btn btn-success btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово: ' + kol2 + '"/><span class="btn btn-success btn-xs" onclick="change_qty(this,1,'+user+')">Ок</span></li></ul>');


                                // el.parent().next().text(kol2);
                                //el.html('<span class="btn btn-success btn-xs fix-width" onclick="change_short(this,2,'+total_qty+');">Готово</span> <a href="#" class="btn btn-success btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово: '+kol2+'"/><span class="btn btn-success btn-xs" onclick="change_qty(this)">Ок</span></li></ul>');
                            });

                        }else{

                            var zamen = element.parent().parent().parent().next().find('div.btn_deff');

                            zamen.each(function (index, el) {
                                el = $(el);
                                //var kol2 = el.parent().prev().text();
                                var dis_id = el.parent().next().next().children().val();
                                el.parent().next().text(0);
                                el.html('<span class="btn btn-danger btn-xs fix-width" onclick="change_short(this,2,' + total  + ',' + user + ');">Готово</span> <a href="#" class="btn btn-danger btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово: 0"/><span class="btn btn-danger btn-xs" onclick="change_qty(this,1,'+user+')">Ок</span></li></ul>');


                                // el.parent().next().text(kol2);
                                //el.html('<span class="btn btn-success btn-xs fix-width" onclick="change_short(this,2,'+total_qty+');">Готово</span> <a href="#" class="btn btn-success btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a><ul class="dropdown-menu list-inline"><li><input type="text" placeholder="Готово: '+kol2+'"/><span class="btn btn-success btn-xs" onclick="change_qty(this)">Ок</span></li></ul>');
                            });

                        }



                    }
                    element.parent().html(to_add);

                }else{
                    alert('Количества в системе не были изменены');
                    throw 'Ошибка';
                }

            },
            type:'POST',
            error:function(obj,err){
                if(err){
                    alert('Изменения не были внесены, возникла нерпедвиденная ошибка');
                    return false;
                    throw 'Ошибка';
                }
            }
        });


    }else{

        alert('Вы ввели не число, попробуйте снова!');
    }



}
function select_status(elem,param){
    var elem = $(elem);
    //В зависимости от выбранного чекбокса производим действия
    if(elem.val() == 1){//Если выбрана кнопка
        $('.tochange').each(function(index,element){

            $(element).attr({'onclick':'change_status(this,1,<?=$auth_status["id"]?>)'});
        })
    }else if(elem.val() == 2){//Если выбрана кнопка склад в меню
        $('.tochange').each(function(index,element){

            $(element).attr({'onclick':'change_status(this,2,<?=$auth_status["id"]?>)'});
        })
    }else if(elem.val() == 3){//Если выбрана кнопка выдано
        $('.tochange').each(function(index,element){

            $(element).attr({'onclick':'change_status(this,3,<?=$auth_status["id"]?>)'});
        })
    }else if(elem.val() == 4 || param==1){//Если выбрана кнопка дата
        if(param){//Если передан param, значит пользователь изменил дату поставки, поставил отличную от дефолтной
            var date = elem.val();

        }else {
            var date = elem.next('input').val();
        }

        $('.tochange').each(function(index,element){
            element=$(element);
            element.attr({'onclick':'change_status(this,4,<?=$auth_status["id"]?>,"'+date+'")'});

        })
    }


}

function change_status(elem,param,user_id,date){
    elem=$(elem);
    if(param == 3){//Установка статуса выдано
        var td = $(elem).parent();
        var to_add = "<button class='btn btn-success btn-xs tochange' onclick='change_status(this,1,"+user_id+")' data_col='ok'>Выдано</button>";

        if(td.parent().next().hasClass('hid')){//Групповое изменение статуса дочерних элементов
                var dis_ids = '';
                td.parent().next().find('button').each(
                    function(index,el){
                        dis_ids += '\''+$(el).parent().attr("id")+'\',';
                        //$(el).parent().html("<button class='btn btn-success btn-xs tochange' onclick='change_status(this,1,"+user_id+")' data_col='ok'>Выдано</button>");

                    });
            dis_ids = dis_ids.slice(0,dis_ids.length-1);

            change_pokup_status(dis_ids,param,td,user_id, date, to_add);
       }else if(td.hasClass('status')) {
           var dis_ids = td.attr('id');
          change_pokup_status(dis_ids, param, td, user_id,date,to_add);
        //
        //    if (td.parent().parent().find('button.btn-success').length == elem.parent().parent().parent().children('tr').length - 2) {
        //        td.parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-success btn-xs tochange' onclick='change_status(this,1,"+user_id+")' data_col='ok'>Выдано</button>");
        //    } else {
        //        if (td.parent().parent().find('button.btn-danger').length > 0) {
        //
        //        } else if (td.parent().parent().find('button.btn-warning').length > 0) {
        //            td.parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-warning btn-xs tochange' onclick='change_status(this,1,"+user_id+")'>Склад</button>");
        //        } else {
        //            td.parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-success btn-xs tochange' onclick='change_status(this,1,"+user_id+")'>Выдано</button>");
        //        }
        //
        //    }
        //
        }

    }else if(param == 2){//Установка статуса склад

        var td = $(elem).parent();
        var to_add = "<button class='btn btn-warning btn-xs tochange' onclick='change_status(this,1,"+user_id+")' data_col='yel'>Склад</button>";

        if(td.hasClass('status')){
            var dis_ids = td.attr('id');
            change_pokup_status(dis_ids,param,td,user_id,date,to_add);
            //if(td.parent().parent().find('button.btn-warning').length == elem.parent().parent().parent().children('tr').length-2){
            //    td.parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-warning btn-xs tochange' onclick='change_status(this,1,"+user_id+")' data_col='yel'>Склад</button>");
            //}else{
            //    if(td.parent().parent().find('button.btn-danger').length>0){
            //
            //    }else if(td.parent().parent().find('button.btn-warning').length>0){
            //        td.parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-warning btn-xs tochange' onclick='change_status(this,1,"+user_id+")'>Склад</button>");
            //    }else{
            //        td.parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-success btn-xs tochange' onclick='change_status(this,1,"+user_id+")'>Склад</button>");
            //    }
            //
            //
            //
            //            //td.parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-warning btn-xs tochange' onclick='change_status(this,1)'>Склад</button>");
            //            //return false;
            //
            //    //elem.parent().parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-danger btn-xs tochange' onclick='change_status(this,1)'>Дефицит</button>");
            //}
        }else if(td.parent().next().hasClass('hid')){
                var dis_ids = '';
                td.parent().next().find('button').each(
                    function(index,el){
                        dis_ids += '\''+$(el).parent().attr("id")+'\',';
                        //$(el).parent().html("<button class='btn btn-warning btn-xs tochange' onclick='change_status(this,1,"+user_id+")' data_col='yel'>Склад</button>");

                    });
            dis_ids = dis_ids.slice(0,dis_ids.length-1);
            change_pokup_status(dis_ids,param,td,user_id,date,to_add);
        }
    }else if(param == 1){//Установка статуса дефицит
        var td = $(elem).parent();
        var new_param = $('div.purchased_status form input:checked').val();
        if(new_param == 4){
            var date1 = $('div.purchased_status form input#datetimepicker1').val();

        }

        var to_add = "<button class='btn btn-danger btn-xs tochange' onclick='change_status(this,"+new_param+","+user_id+",\""+date1+"\")'>Дефицит</button>";
        if(elem.parent().parent().next().hasClass('hid')){
            var dis_ids = '';
            elem.parent().parent().next().find('button').each(
                function(index,el){
                    dis_ids += '\''+$(el).parent().attr("id")+'\',';
                    //$(el).parent().html("<button class='btn btn-danger btn-xs tochange' onclick='change_status(this,1,"+user_id+")'>Дефицит</button>");

                });
            dis_ids = dis_ids.slice(0,dis_ids.length-1);
            change_pokup_status(dis_ids,param,elem.parent(),user_id,date,to_add);
        }else if(elem.parent().hasClass('status')){
            var dis_ids = elem.parent().attr('id');
            change_pokup_status(dis_ids,param,elem.parent(),user_id,date,to_add);
            //if(elem.parent().parent().parent().find('button.btn-danger').length+1 == elem.parent().parent().parent().children('tr').length-2){
            //    elem.parent().parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-success btn-xs tochange' onclick='change_status(this,1,"+user_id+")'>Выдано</button>");
            //}else{
            //    elem.parent().parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-danger btn-xs tochange' onclick='change_status(this,1,"+user_id+")'>Дефицит</button>");
            //}
        }

        //elem.parent().html("<button class='btn btn-danger btn-xs tochange' onclick='change_status(this,1,"+user_id+")'>Дефицит</button>");
    }else if(param == 4){//Установка статуса : дата поставки
        var td = elem.parent();
        var to_add = "<button class='btn btn-danger btn-xs tochange date' onclick='change_status(this,1,"+user_id+")'>"+date+"</button>";
        if(td.parent().next().hasClass('hid')){//Проверка: находимся в детале, входящей в несколько сборок и имеющей дочерние элементы
            var dis_ids = '';
            td.parent().next().find('button').each(
                function(index,el){
                    dis_ids += '\''+$(el).parent().attr("id")+'\',';
                    //$(el).parent().html("<button class='btn btn-danger btn-xs tochange date' onclick='change_status(this,1,"+user_id+")'>"+date+"</button>");

                });
            dis_ids = dis_ids.slice(0,dis_ids.length-1);
            change_pokup_status(dis_ids,param,td,user_id,date,to_add);
        }else if(td.hasClass('status')){//Проверка мы меняем значение
            var dis_ids = td.attr('id');
            change_pokup_status(dis_ids,param,td,user_id,date,to_add);
            //if(elem.parent().parent().parent().find('button.date').length+1 == elem.parent().parent().parent().children('tr').length-2){
                //В том случае, если нужно изменить дату у родительского элемента
                //var date1='';
                //td.parent().parent().find('button.btn-danger').each(function(index,el){
                //    //Ищем максимально удаленную дату поставки
                //
                //    if($(el).text() == 'Дефицит'){
                //        date1 = 'Дефицит';
                //        return false;
                //    } else if(date1<$(el).text()){
                //        date1 = $(el).text();
                //    }
                //});
                //В date1 попадет макисальная дата

                //заменяем дату в родительском блоке
                //td.parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-danger btn-xs tochange' onclick='change_status(this,1,"+user_id+")'>"+date1+"</button>");
           // }else{
              //  elem.parent().parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-danger btn-xs tochange' onclick='change_status(this,1)'>Дефицит</button>");
           // }
        }


    }

}

function change_pokup_status(dis_id,status,elem,user_id,date,to_add){
//alert(dis_id);alert(status);
            /*
            for(index in arguments){
                alert(arguments[index]);
            }
            */
        switch(status){
            case 3:
            st = 2;
            break;
            case 1:
            case 4:
            st = 0;
            break;
            case 2:
            st = 1;
            break;
        }

        $.ajax({
            url: URL_ + 'ajax.php',
            data:{dis_id:dis_id,status:st,date:date,user_id:user_id},
            success:function(data){
                if(data != 1){
                    alert('Статус в системе не был изменен, произошла непредвиденная ошибка');
                }else{//
                    if(status == 3){
                        //alert(1111);
                            if(elem.parent().next().hasClass('hid')){//Групповое изменение статуса дочерних элементов
                                //var dis_ids = '';
                                elem.parent().next().find('button').each(
                                    function(index,el){
                                        //dis_ids += '\''+$(el).parent().attr("id")+'\',';
                                        $(el).parent().html("<button class='btn btn-success btn-xs tochange' onclick='change_status(this,1,"+user_id+")' data_col='ok'>Выдано</button>");

                                    });
                                //dis_ids = dis_ids.slice(0,dis_ids.length-1);
                                elem.html(to_add);
                            }else if(elem.hasClass('status')){
                                elem.html(to_add);

                                if (elem.parent().parent().find('button.btn-success').length == elem.parent().parent().parent().children('tr').length - 2) {
                                    elem.parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-success btn-xs tochange' onclick='change_status(this,1,"+user_id+")' data_col='ok'>Выдано</button>");
                                } else {
                                    if (elem.parent().parent().find('button.btn-danger').length > 0) {

                                    } else if (elem.parent().parent().find('button.btn-warning').length > 0) {
                                        elem.parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-warning btn-xs tochange' onclick='change_status(this,1,"+user_id+")'>Склад</button>");
                                    } else {
                                        elem.parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-success btn-xs tochange' onclick='change_status(this,1,"+user_id+")'>Выдано</button>");
                                    }

                                }




                            }
                        }else if(status == 2){
                            elem.html(to_add);
                             if(elem.hasClass('status')) {
                                 if (elem.parent().parent().find('button.btn-warning').length == elem.parent().parent().parent().children('tr').length - 2) {
                                     elem.parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-warning btn-xs tochange' onclick='change_status(this,1," + user_id + ")' data_col='yel'>Склад</button>");
                                 } else {
                                     if (elem.parent().parent().find('button.btn-danger').length > 0) {

                                     } else if (elem.parent().parent().find('button.btn-warning').length > 0) {
                                         elem.parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-warning btn-xs tochange' onclick='change_status(this,1," + user_id + ")'>Склад</button>");
                                     } else {
                                         elem.parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-success btn-xs tochange' onclick='change_status(this,1," + user_id + ")'>Склад</button>");
                                     }


                                     //td.parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-warning btn-xs tochange' onclick='change_status(this,1)'>Склад</button>");
                                     //return false;

                                     //elem.parent().parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-danger btn-xs tochange' onclick='change_status(this,1)'>Дефицит</button>");
                                 }

                             }else if(elem.parent().next().hasClass('hid')){

                                 elem.parent().next().find('button').each(
                                     function(index,el){
                                         //dis_ids += '\''+$(el).parent().attr("id")+'\',';
                                         $(el).parent().html("<button class='btn btn-warning btn-xs tochange' onclick='change_status(this,1,"+user_id+")' data_col='yel'>Склад</button>");

                                     });

                                 elem.html(to_add);

                             }

                        }else if(status == 1){
                            elem.html(to_add);
                            if(elem.hasClass('status')) {
                                if(elem.parent().parent().find('button.btn-danger').length+1 == elem.parent().parent().parent().children('tr').length-2){
                                    elem.parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-success btn-xs tochange' onclick='change_status(this,1,"+user_id+")'>Выдано</button>");
                                }else{
                                    elem.parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-danger btn-xs tochange' onclick='change_status(this,1,"+user_id+")'>Дефицит</button>");
                                }

                            }else if(elem.parent().next().hasClass('hid')){
                                elem.parent().next().find('button').each(
                                    function(index,el){
                                        //dis_ids += '\''+$(el).parent().attr("id")+'\',';
                                        $(el).parent().html("<button class='btn btn-danger btn-xs tochange' onclick='change_status(this,1,"+user_id+")'>Дефицит</button>");

                                    });

                            }

                       }else if(status == 4){

                            if(elem.parent().next().hasClass('hid')){

                                elem.parent().next().find('button').each(
                                    function(index,el){
                                        //dis_ids += '\''+$(el).parent().attr("id")+'\',';
                                        $(el).parent().html("<button class='btn btn-danger btn-xs tochange date' onclick='change_status(this,1,"+user_id+")'>"+date+"</button>");

                                    });

                                elem.html(to_add);
                            }else if(elem.hasClass('status')){
                                elem.html(to_add);
                                var date1='';
                                elem.parent().parent().find('button.btn-danger').each(function(index,el){
                                    //Ищем максимально удаленную дату поставки

                                    if($(el).text() == 'Дефицит'){
                                        date1 = 'Дефицит';
                                        return false;
                                    } else if(date1<$(el).text()){
                                        date1 = $(el).text();
                                    }
                                });
                                //alert(date1);
                                elem.parent().parent().parent().parent().parent().prev().find('.status_spis').html("<button class='btn btn-danger btn-xs tochange' onclick='change_status(this,1,"+user_id+")'>"+date1+"</button>");
                            }


                    }

                }
            },
            type:'POST',
            beforeSend:function(obj, sett){
                elem.children('button').attr({'disabled':true});

            },
            complete:function(){
                elem.children('button').attr({'disabled':false});

            },
            error:function(obj, error){
                alert('Изменения не были внесены, возникла непредвиденная ошибка');
            }

        });




}

function search_status(param,main_el){
    var tr = $('tr.status_spis_tr');
    $('tr.hid').each(function(index,el){$(el).css({'display':'none'})});
      switch (param){
        case 'deff':

        $(main_el).toggleClass('btn-danger');
        $(main_el).toggleClass('btn-default');
        //tr.css({'display':'table-row'});

        if($(main_el).hasClass('btn-danger')){
            tr.find('td.main_td').each(function(index,el){
                el = $(el);
                if(el.children('button').hasClass('btn-danger')){
                    if(!el.parent(tr).hasClass('hid_tr')) {
                        el.parent('tr').css({'display':'table-row'});

                    }
                    el.parent('tr').removeClass('hid_tr_st');
                }
            })}
        else{tr.find('td.main_td').each(function(index,el){
            el = $(el);
            if(el.children('button').hasClass('btn-danger')){
                el.parent('tr').css({'display':'none'});
                el.parent('tr').addClass('hid_tr_st');
            }})

        }

        break;
        case 'sklad':
        $(main_el).toggleClass('btn-warning');
        $(main_el).toggleClass('btn-default');
        //tr.css({'display':'table-row'});
        if($(main_el).hasClass('btn-warning')){
            tr.find('td.main_td').each(function(index,el){
                el = $(el);
                if(el.children('button').hasClass('btn-warning')){
                    if(!el.parent(tr).hasClass('hid_tr')){
                        el.parent('tr').css({'display':'table-row'});

                    }
                    el.parent('tr').removeClass('hid_tr_st');
                }
            })}
        else{tr.find('td.main_td').each(function(index,el){
            el = $(el);
            if(el.children('button').hasClass('btn-warning')){
                el.parent('tr').css({'display':'none'});
                el.parent('tr').addClass('hid_tr_st');
            }})

        }
        break;
        case 'ok':
        $(main_el).toggleClass('btn-success');
        $(main_el).toggleClass('btn-default');
        //tr.css({'display':'table-row'});
        if($(main_el).hasClass('btn-success')){
            tr.find('td.main_td').each(function(index,el){
                    el = $(el);
                    if(el.children('button').hasClass('btn-success')){
                        if(!el.parent(tr).hasClass('hid_tr')) {
                            el.parent('tr').css({'display': 'table-row'});

                        }
                        el.parent('tr').removeClass('hid_tr_st');
                    }
            })}
        else{tr.find('td.main_td').each(function(index,el){
                el = $(el);
                if(el.children('button').hasClass('btn-success')){
                    el.parent('tr').css({'display':'none'});
                    el.parent('tr').addClass('hid_tr_st');
                }})

            }
        break;
      case 'all':
      //$(main_el).toggleClass('btn-primary');
     //$(main_el).toggleClass('btn-default');
          if($(main_el).hasClass('btn-primary')){
              tr.find('td.main_td').each(function(index,el){
                  el = $(el);
                 // if(el.children('button').hasClass('btn-success') ){
                      if(!el.parent('tr').hasClass('hid_tr')) {
                          el.parent('tr').css({'display': 'table-row'});

                      }
                      el.parent('tr').removeClass('hid_tr_st');
                  $('#danger').addClass('btn-danger');
                  $('#danger').removeClass('btn-default');
                  $('#stock').addClass('btn-warning');
                  $('#stock').removeClass('btn-default');
                  $('#ok').addClass('btn-success');
                  $('#ok').removeClass('btn-default');
                  //}
              })}
          else{tr.find('td.main_td').each(function(index,el){
              el = $(el);
          //    if(el.children('button').hasClass('btn-success')){
                  el.parent('tr').css({'display':'none'});
                  el.parent('tr').addClass('hid_tr_st');
            //  }
          })

              $('#danger').removeClass('btn-danger');
              $('#danger').addClass('btn-default');
              $('#stock').removeClass('btn-warning');
              $('#stock').addClass('btn-default');
              $('#ok').removeClass('btn-success');
              $('#ok').addClass('btn-default');
          }


      break;
    }
}

function search_type(param,main_el){

    var tr = $('tr.status_spis_tr');
    $('tr.hid').each(function(index,el){$(el).css({'display':'none'})});
    switch (param){
        case 'resis':
           var det = tr.find('td.detail');
            if($(main_el).hasClass('btn-primary')){
                det.each(function(index,el){
                    if(600<=parseInt($(el).text().slice(0,3)) &&  parseInt($(el).text().slice(0,3)) <= 609){
                        $(el).parent('tr').css({'display':'none'});
                        $(el).parent('tr').addClass('hid_tr');
                    }
                })
            }else{
                det.each(function(index,el){

                        if(600<=parseInt($(el).text().slice(0,3)) &&  parseInt($(el).text().slice(0,3)) <= 609){
                            if(!$(el).parent('tr').hasClass('hid_tr_st')){
                            $(el).parent('tr').css({'display':'table-row'});

                        }
                        $(el).parent('tr').removeClass('hid_tr');
                    }

                })

            }

        break;
        case 'condens':
            var det = tr.find('td.detail');
            if($(main_el).hasClass('btn-primary')){
                det.each(function(index,el){
                    if(610<=parseInt($(el).text().slice(0,3)) &&  parseInt($(el).text().slice(0,3)) <= 619){
                        $(el).parent('tr').css({'display':'none'});
                        $(el).parent('tr').addClass('hid_tr');
                    }
                })
            }else{
                det.each(function(index,el){

                    if(610<=parseInt($(el).text().slice(0,3)) &&  parseInt($(el).text().slice(0,3)) <= 619){
                        if(!$(el).parent('tr').hasClass('hid_tr_st')){
                        $(el).parent('tr').css({'display':'table-row'});

                        }
                        $(el).parent('tr').removeClass('hid_tr');
                    }

                })

            }
        break;
        case 'poluprov':
            var det = tr.find('td.detail');
            if($(main_el).hasClass('btn-primary')){
                det.each(function(index,el){
                    if(620<=parseInt($(el).text().slice(0,3)) &&  parseInt($(el).text().slice(0,3)) <= 629){
                        $(el).parent('tr').css({'display':'none'});
                        $(el).parent('tr').addClass('hid_tr');
                    }
                })
            }else{
                det.each(function(index,el){

                        if(620<=parseInt($(el).text().slice(0,3)) &&  parseInt($(el).text().slice(0,3)) <= 629){
                            if(!$(el).parent('tr').hasClass('hid_tr_st')){
                            $(el).parent('tr').css({'display':'table-row'});

                        }
                        $(el).parent('tr').removeClass('hid_tr');
                    }

                })

            }
            break;
        case 'transf':
            var det = tr.find('td.detail');
            if($(main_el).hasClass('btn-primary')){
                det.each(function(index,el){
                    if(630<=parseInt($(el).text().slice(0,3)) &&  parseInt($(el).text().slice(0,3)) <= 649){
                        $(el).parent('tr').css({'display':'none'});
                        $(el).parent('tr').addClass('hid_tr');
                    }
                })
            }else{
                det.each(function(index,el){

                        if(630<=parseInt($(el).text().slice(0,3)) &&  parseInt($(el).text().slice(0,3)) <= 649){
                            if(!$(el).parent('tr').hasClass('hid_tr_st')){
                            $(el).parent('tr').css({'display':'table-row'});

                         }
                        $(el).parent('tr').removeClass('hid_tr');
                    }

                })

            }
            break;
        case 'rele':
            var det = tr.find('td.detail');
            if($(main_el).hasClass('btn-primary')){
                det.each(function(index,el){
                    if(660<=parseInt($(el).text().slice(0,3)) &&  parseInt($(el).text().slice(0,3)) <= 669){
                        $(el).parent('tr').css({'display':'none'});
                        $(el).parent('tr').addClass('hid_tr');
                    }
                })
            }else{
                det.each(function(index,el){

                        if(660<=parseInt($(el).text().slice(0,3)) &&  parseInt($(el).text().slice(0,3)) <= 669){
                            if(!$(el).parent('tr').hasClass('hid_tr_st')){
                            $(el).parent('tr').css({'display':'table-row'});

                            }
                            $(el).parent('tr').removeClass('hid_tr');
                    }

                })

            }
            break;
        case 'diff':
            var det = tr.find('td.detail');
            if($(main_el).hasClass('btn-primary')){
                det.each(function(index,el){
                    if(670<=parseInt($(el).text().slice(0,3)) &&  parseInt($(el).text().slice(0,3)) <= 679){
                        $(el).parent('tr').css({'display':'none'});
                        $(el).parent('tr').addClass('hid_tr');
                    }
                })
            }else{
                det.each(function(index,el){

                        if(670<=parseInt($(el).text().slice(0,3)) &&  parseInt($(el).text().slice(0,3)) <= 679){
                            if(!$(el).parent('tr').hasClass('hid_tr_st')){
                                $(el).parent('tr').css({'display':'table-row'});

                            }
                            $(el).parent('tr').removeClass('hid_tr');
                    }

                })

            }
            break;
        case 'podship':
            var det = tr.find('td.detail');
            if($(main_el).hasClass('btn-primary')){
                det.each(function(index,el){
                    if(680<=parseInt($(el).text().slice(0,3)) &&  parseInt($(el).text().slice(0,3)) <= 689){
                        $(el).parent('tr').css({'display':'none'});
                        $(el).parent('tr').addClass('hid_tr');
                    }
                })
            }else{
                det.each(function(index,el){

                        if(680<=parseInt($(el).text().slice(0,3)) &&  parseInt($(el).text().slice(0,3)) <= 689){
                            if(!$(el).parent('tr').hasClass('hid_tr_st')){
                            $(el).parent('tr').css({'display':'table-row'});
                            }
                        $(el).parent('tr').removeClass('hid_tr');
                        }

                })

            }
            break;
        case 'all':
            var det = tr.find('td.detail');

            if($(main_el).hasClass('btn-primary')){
                det.each(function(index,el){

                        $(el).parent('tr').css({'display':'none'});
                        $(el).parent('tr').addClass('hid_tr');

                })
                $('#resis').removeClass('btn-primary');
                $('#resis').addClass('btn-default');
                $('#condens').removeClass('btn-primary');
                $('#condens').addClass('btn-default');
                $('#poluprov').removeClass('btn-primary');
                $('#poluprov').addClass('btn-default');
                $('#transf').removeClass('btn-primary');
                $('#transf').addClass('btn-default');
                $('#rele').removeClass('btn-primary');
                $('#rele').addClass('btn-default');
                $('#diff').removeClass('btn-primary');
                $('#diff').addClass('btn-default');
                $('#podship').removeClass('btn-primary');
                $('#podship').addClass('btn-default');


            }else{
                det.each(function(index,el){

                        if(!$(el).parent('tr').hasClass('hid_tr_st')){
                            $(el).parent('tr').css({'display':'table-row'});
                             //$(el).parent('tr').removeClass('hid_tr_st');
                        }
                        $(el).parent('tr').removeClass('hid_tr');

                })
                $('#resis').addClass('btn-primary');
                $('#resis').removeClass('btn-default');
                $('#condens').addClass('btn-primary');
                $('#condens').removeClass('btn-default');
                $('#poluprov').addClass('btn-primary');
                $('#poluprov').removeClass('btn-default');
                $('#transf').addClass('btn-primary');
                $('#transf').removeClass('btn-default');
                $('#rele').addClass('btn-primary');
                $('#rele').removeClass('btn-default');
                $('#diff').addClass('btn-primary');
                $('#diff').removeClass('btn-default');
                $('#podship').addClass('btn-primary');
                $('#podship').removeClass('btn-default');






            }

        break;
    }


        $(main_el).toggleClass('btn-primary');
        $(main_el).toggleClass('btn-default');


}
//фильтр по получателю
function search_recip(main_el, ceh){
    if($(main_el).hasClass('btn-primary')){

        $('.ceh_main').each(function(index,el){
            if($(el).text().slice(0,4) == ceh){
                $(el).parent('tr').addClass('hid_recip');
            }
        });

        $('.ceh').each(function(index,el){
            if($(el).text().slice(0,4) == ceh){
                //Получаем значения общее, ругулировки и количесвта в дочерних ячейках

                var com_m = +$(el).prev().text();
                var eff_m = +$(el).prev().prev().text();
                var qty_m = +$(el).prev().prev().prev().text();

                var par_tr = $(el).parent().parent().parent().parent().parent().prev();


                par_tr.children('td.qty').text(Math.round((+par_tr.children('td.qty').text()-qty_m)*100)/100);
                par_tr.children('td.eff').text(Math.round((+par_tr.children('td.eff').text()-eff_m)*100)/100);
                par_tr.children('td.com').text(Math.round((+par_tr.children('td.com').text()-com_m)*100)/100);

                $(el).parent('tr').addClass('hid_recip');
            }
        });

        $('tr.hid').each(function(index,el){
            var sum=0;
             $(el).find('tr').each(function(index2,el2){
                if($(el2).css('display') == 'table-row'){
                    sum+=1;
                }
             })

            if(sum == 2) {
                $(el).addClass('hid_recip');
                $(el).prev().addClass('hid_recip');
            }
        });
    }else{
        $('.ceh_main').each(function(index,el){
            if($(el).text().slice(0,4) == ceh){
                $(el).parent('tr').removeClass('hid_recip');
            }
        });

        $('.ceh').each(function(index,el){
            if($(el).text().slice(0,4) == ceh){

                //Получаем значения общее, ругулировки и количесвта в дочерних ячейках

                var com_m = +$(el).prev().text();
                var eff_m = +$(el).prev().prev().text();
                var qty_m = +$(el).prev().prev().prev().text();

                var par_tr = $(el).parent().parent().parent().parent().parent().prev();


                par_tr.children('td.qty').text(Math.round((+par_tr.children('td.qty').text()+qty_m)*100)/100);
                par_tr.children('td.eff').text(Math.round((+par_tr.children('td.eff').text()+eff_m)*100)/100);
                par_tr.children('td.com').text(Math.round((+par_tr.children('td.com').text()+com_m)*100)/100);







                $(el).parent('tr').removeClass('hid_recip');
            }
        });

        $('tr.hid').each(function(index,el){
            var sum=0;
            $(el).find('tr').each(function(index2,el2){
                if($(el2).css('display') == 'table-row'){
                    sum+=1;
                }
            })

            if(sum > 2) {
                $(el).removeClass('hid_recip');
                //$(el).css({'display':'table-row'});
                $(el).prev().removeClass('hid_recip');
            }
        });



    }
    $(main_el).toggleClass('btn-primary');
    $(main_el).toggleClass('btn-default');
}



// Функция получения рабочих по нарядам
function send_finery(e,year,month,timed) {
    e.preventDefault();
    var el = $('#finery');

        var finery = parseInt(el.val());
        var period = $('#period').val();
        var ceh = $('#ceh').val();

        $.ajax({
            type: 'post',
            success: function (data) {
                $('#added_nar').remove();
                data = JSON.parse(data);

                var res = '<div class="row" id="added_nar"><div class="col-md-12"><h3>Полученные наряды</h3>';





                for(index in data){
                    var restday = (data[index].restday > 0)?"btn btn-primary":"btn btn-default";
                    var overtime = (data[index].overtime > 0)?"btn btn-primary":"btn btn-default";
                    var extra = (data[index].extra > 0)?"btn btn-primary":"btn btn-default";
                    var sostav = (data[index].NU>0)?'<button class="btn btn-default" data-toggle="modal" data-target="#sostav_nar" onclick="show_brig('+"'"+data[index].NC+data[index].NU+data[index].NB+"'"+','+data[index].id+');">Состав бригады</button>':'';
                    res+='<div class="row mrg30" ><div class="col-md-2">';
                    res+='<div class="btn-group-vertical"><button class="'+restday+'" onclick="ch_overwork(1,this,'+data[index].id+');">Выходной день</button><button class="'+overtime+'" onclick="ch_overwork(2,this,'+data[index].id+');">Сверхурочные</button><button class="'+extra+'" onclick="ch_overwork(3,this,'+data[index].id+' )">За доплату</button>'+sostav+' <div class="input-group"><input type="text" class="form-control" id="ttab"><span class="input-group-btn"><button class="btn btn-default" type="button" onclick="add_time_tab(this,'+data[index].id+');">Т<sub>таб</sub></button></span></div></div></div>';
                    res+='<div class="col-md-10">';
                    res+='<div class="table-responsive">';
                    res+='<table class="table table-stripped">';
                    res+='<tr><th>ID</th><th>ЦЕХ</th><th>Участок</th><th>Бригада</th><th>TN</th><th>FIO</th><th>PROF</th><th>LK</th><th>NN</th><th>QVR</th><th>DOP</th><th>TR</th><th>VSHC</th><th>VSHM</th><th>VSHMI</th><th>VPZC</th><th>VPZM</th><th>VPZMI</th><th>BK</th><th>KVN</th><th>ZAK</th><th>KORC</th><th>KPO</th><th>KVO</th><th>PO</th><th>PECH</th><th>KTY1</th><th>KTY2</th><th>NPROF</th><th>NR</th><th>SPROF</th><th>SR</th><th>KODRAB</th><th>WORKERS117</th><th>count_tosn</th><th>count_zarosn</th><th>count_totkl</th><th>count_zarotkl</th><th>count_zarvredosnl</th><th>count_zarvredotkl</th><th>count_tit</th><th>count_zarit</th><th>count_restday</th><th>count_overtime</th><th>count_prem</th><th>count_premvred</th><th>count_tmezh</th><th>count_zarmezh</th><th>count_lk</th><th>count_dopbr</th><th>count_itog</th></tr>';

                    res+='<tr><td class="min_50">'+data[index].id+'</td><td><span class="label label-danger">'+data[index].NC+'</span></td><td><span class="label label-warning">'+data[index].NU+'</td><td>'+data[index].NB+'</td><td>'+data[index].TN+'</td><td>'+data[index].FIO+'</td><td>'+data[index].PROF+'</td><td>'+data[index].LK+'</td><td>'+data[index].NN+'</td><td>'+data[index].QVR+'</td><td>'+data[index].DOP+'</td><td>'+data[index].TR+'</td><td>'+data[index].VSHC+'</td><td>'+data[index].VSHM+'</td><td>'+data[index].VSHMI+'</td><td>'+data[index].VPZC+'</td><td>'+data[index].VPZM+'</td><td>'+data[index].VPZMI+'</td><td>'+data[index].BK+'</td><td>'+data[index].KVN+'</td><td>'+data[index].ZAK+'</td><td>'+data[index].KORC+'</td><td>'+data[index].KPO+'</td><td>'+data[index].KVO+'</td><td>'+data[index].PO+'</td><td>'+data[index].PECH+'</td><td>'+data[index].KTY1+'</td><td>'+data[index].KTY2+'</td><td>'+data[index].NPROF+'</td><td>'+data[index].NR+'</td><td>'+data[index].SPROF+'</td><td>'+data[index].SR+'</td><td>'+data[index].KODRAB+'</td><td>'+data[index].WORKERS117+'</td><td>'+data[index].count_tosn+'</td><td>'+data[index].count_zarosn+'</td><td>'+data[index].count_totkl	+'</td><td>'+data[index].count_zarotkl+'</td><td>'+data[index].count_zarvredosn	+'</td><td>'+data[index].count_zarvredotkl+'</td><td>'+data[index].count_tit	+'</td><td>'+data[index].count_zarit	+'</td><td>'+data[index].count_restday+'</td><td>'+data[index].count_overtime	+'</td><td>'+data[index].count_prem	+'</td><td>'+data[index].count_premvred	+'</td><td>'+data[index].count_tmezh	+'</td><td>'+data[index].count_zarmezh	+'</td><td>'+data[index].count_lk	+'</td><td>'+data[index].count_dopbr	+'</td><td>'+data[index].count_itog	+'</td></tr>';

                    res+='</table>';
                    res+='</div>';
                    res+='</div></div></div><div class="row"><div class="col-md-2 col-md-push-10"><button class="btn btn-primary mrg20" onclick="clr_nar();">Обновить</button></div></div>';
                }



                $('form.naryad').after(res);


            },
            data: {finery: finery,year:year,month:month,timed:timed,period:period,ceh:ceh},
            url: URL_ + 'ajax.php'

        })


}
//Установка статуса : выходной день, сверхурочно, за доплату
function ch_overwork(param,  elem,id){
    elem = $(elem);
    switch(param){
        case 1:
        var field = 'restday';
        break;
        case 2:
        var field = 'overtime';
        break;
        case 3:
        var field = 'extra';
        break;

    }
   if(elem.hasClass('btn-default')){
       send_ch_overwork(field,id,1,elem);
   }else if(elem.hasClass('btn-primary')){
       send_ch_overwork(field,id,0,elem);
    }
}

function send_ch_overwork(field,id,par,elem){

    $.ajax({
        type:'post',
        url: URL_ + 'ajax.php',
        data:{field:field,id:id,par:par},
        success:function(data){

            if(data != 1){
                alert('Не удалось обновить данные, обратитесь к разработчику');
                $('#added_nar').after('<div class="alert alert-dismissible alert-danger"><strong>Возникла ошибка, обратитесь к разработчику</strong></div>');
            }else{
                switch(field){
                    case 'restday':
                    var status = 'Выходной день';
                    break;
                    case 'overtime':
                    var status = 'Сверхурочные';
                    break;
                    case 'extra':
                    var status = 'За доплату';
                    break;

                }

                if(par == 1){

                    elem.removeClass('btn-default');
                    elem.addClass('btn-primary');
                    $('#added_nar').after('<div class="alert alert-dismissible alert-success">Был добавлен статус <strong>'+status+'</strong></div>');

                }else if(par == 0){
                    elem.removeClass('btn-primary');
                    elem.addClass('btn-default');
                    $('#added_nar').after('<div class="alert alert-dismissible alert-success">Был снят статус <strong>'+status+'</strong></div>');
                }


            }
        }


    })


}
//Функция вызова всплывающего окна, с составом бригад
function show_brig(param,id){
    param1 = '#br'+param;
    $('#to_append').html('');
    var res = '<div class="modal-header" ><button class="close" type="button" data-dismiss="modal">×</button>';
    res +=  '<h4 class="modal-title">Состав бригады</h4>';
    res += '</div>';
    res+='<table class="table table-bordered">';
    $(param1).find('tr.tr'+param).each(function(i,el){
        res+='<tr>';
        res+='<td>'+$(el).find('td.tn'+param).html()+'</td>';
        res+='<td>'+$(el).find('td.fio'+param).html()+'</td><td><input type="text" value="1.000"/></td><td><input type="checkbox"/></td>';
        res+='</tr>';
    });
    res+='</table>';
    res+='<div class="modal-footer"><button class="btn btn-default" data-dismiss="modal" onclick="upd_naryad('+id+');">Занести в базу</button></div>';

    $('#to_append').append(res);


}
//Обновление наряда (поля extraworkers)
function upd_naryad(id){
    var str = '';
    $('#to_append input:checkbox:checked').each(function(i,el){
        el = $(el);
        str+=el.parent().prev().prev().prev().text()+':';
        str+=el.parent().prev().children().val()+';';



    });
    str = str.slice(0,str.length-1);
    $.ajax({
        type:'post',
        url: URL_ + 'ajax.php',
        data:{str_nar:str,id:id},
        success:function(data){
            if(data != 1) {
                alert('Не удалось обновить данные');
                $('#added_nar').after('<div class="alert alert-dismissible alert-danger"><strong>Возникла ошибка, обратитесь к разработчику, либо вы ввели те же самые данные</strong></div>');
            }else{
                $('#added_nar').after('<div class="alert alert-dismissible alert-success">Был занесен КТУ по выбранным рабочим, строка записанная в базу: <strong>'+str+'</strong></div>');
            }
        }


    })


}
//Очистка нарядов
function clr_nar(){
    $('#added_nar').html('');
    $('.alert').remove();
    var finery = $('#finery');
    finery.val('');
    finery.focus();
}

function note_docs(param){
    switch(param){
        case 'all_docs':

            $('input:checkbox').attr({'checked':'checked'});
        break;




    }




}

function create_docs(disp,all,elem,way,file){

    if(all=='all'){
        var checked = $('input:checkbox:checked');
        var i = 0;
        send_aj(checked,disp,i,checked.length-1)

    }else{
        var res = {};
        switch(all) {
            case 'nekomp_gol_spec':
                res.nekomp='1';
                break;
            case 'nekomp_spec':
                res.nekomp='2';
                break;
            case 'nekomp_routes':
                res.nekomp='3';
                break;
            case 'nekomp_names':
                res.nekomp='4';
                break;
            case 'vedom_apply':
                res.apply = '1';
                break;
            case 'vedom_apply_kod':
                var str = '';
                var ceh  = $('#apply_ceh').val();


                res.apply_kod = ceh;
            break;
            case 'vedom_apply_kod_145':
                res.apply_kod = {0:145};
            break;
            case 'vedom_apply_kod_154':
                res.apply_kod = {0:154};
            break;
            case 'vedom_apply_prib':
                res.apply_kod_prib = $('#apply_ceh_prib').val();
            break;
            case 'trud_ceh':
                var json = true;
                res.trud_ceh = 1;
                var head = $('#head').val();
                var tsht = $('#tsht').val();
                var tpz = $('#tpz').val();
            break;
            case 'trud_prib':
                var json = true;
                res.trud_ceh = 2;
                var head = $('#head').val();
                var tsht = $('#tsht').val();
                var tpz = $('#tpz').val();
            break;
            case 'trud_prib_ceh':
                var json = true;
                res.trud_ceh = 3;
                var head = $('#head').val();
                var tsht = $('#tsht').val();
                var tpz = $('#tpz').val();
            break;
            case 'trud_svod':
                var json = true;
                res.trud_ceh = 4;
                var head = $('#head').val();
                var tsht = $('#tsht').val();
                var tpz = $('#tpz').val();
                break;
            case 'uch_trud':
                var json = true;
                res.trud_ceh = 5;
                var head = $('#head').val();
                var tsht = $('#tsht').val();
                var tpz = $('#tpz').val();
            break;
            case 'nomen_vedom':
               // var json = true;
                res.nomen_vedom = 1;
                var head = $('#head').val();
                var tsht = $('#tsht').val();
                var tpz = $('#tpz').val();
            break;
            case 'nomen_vedom_kod':
                //var json = true;
                var head = $('#head').val();
                var tsht = $('#tsht').val();
                var tpz = $('#tpz').val();

                var ceh  = $('#nomen_vedom_kod').val();

                res.nomen_vedom = ceh;
            break;
            case 'nomen_vedom_pdo':
            var head = $('#head').val();
            var tsht = $('#tsht').val();
            var tpz = $('#tpz').val();
            res.nomen_vedom = 'pdo';

            break;
            case 'nomen_vedom_mck':
            var head = $('#head').val();
            var tsht = $('#tsht').val();
            var tpz = $('#tpz').val();
            res.nomen_vedom = 'mck';
            break;
            case 'nomen_vedom_mck_kod':
            var head = $('#head').val();
            var tsht = $('#tsht').val();
            var tpz = $('#tpz').val();
            res.nomen_vedom_mck_kod = $('#nomen_vedom_mck_kod').val();
            break;
            case 'nomen_vedom_mck_kod_112':
            var head = $('#head').val();
            var tsht = $('#tsht').val();
            var tpz = $('#tpz').val();
            res.nomen_vedom_mck_kod   = {0:112};
            break;
            case 'nomen_vedom_uch':
            var head = $('#head').val();
            var tsht = $('#tsht').val();
            var tpz = $('#tpz').val();
            res.nomen_vedom_uch   = 'uch'
            break;
            case 'nomen_uch_kod':

            var head = $('#head').val();
            var tsht = $('#tsht').val();
            var tpz = $('#tpz').val();
            res.nomen_vedom_uch   =  $('#nomen_uch_kod').val();
            break;
            case 'nomen_uch_kod_120':

            var head = $('#head').val();
            var tsht = $('#tsht').val();
            var tpz = $('#tpz').val();
            res.nomen_vedom_uch   =  {0:120};
            break;
        }

        $.ajax({
            type:'post',
            url: URL_ + 'ajax.php',
            data:{docs_create:res,disp:disp,way:way,file:file,head:head,tsht:tsht,tpz:tpz},
            beforesend:$(elem).attr({'disabled':true}),
            success:function(data){alert(data);


                    if(res.apply_kod  || all == 'nomen_vedom_kod' || all == 'nomen_vedom_mck_kod'){

                        // $html = $(elem).parent().next().html();
                        $(elem).parent().next().children().append(data);

                    }else{

                        if(data){
                            $(elem).parent().next().empty();

                            if(json == true){

                                $(elem).parent().next().next().empty();
                                data = JSON.parse(data);
                                $(elem).parent().next().next().append(data[1]);
                                $(elem).parent().next().append(data[0]);

                            }else{

                                $(elem).parent().next().append(data);

                            }
                        }


                    }




                    //window.location.reload();
                },
            complete: $(elem).attr({'disabled':false})
            });



        }







}

function send_aj(elems,disp,i,dlina){
    var res = {nekomp:''};
    switch($(elems[i]).attr('name')){
        case 'nekomp_gol_spec':
            res.nekomp='1';
            break;
        case 'nekomp_spec':
            res.nekomp='2';
            break;
        case 'nekomp_routes':
            res.nekomp='3';
            break;
        case 'nekomp_names':
            res.nekomp='4';
            break;
        case 'vedom_apply':
            res.apply = '1';
            break;
    }

    var way = $(elems[i]).attr('data-way');
    var file = $(elems[i]).attr('data-file');


    $.ajax({
        type:'post',
        url: URL_ + 'ajax.php',
        data:{docs_create:res,disp:disp,way:way,file:file},
        //beforesend:$(el).attr({'disabled':true}),

        success:function(data){
            $(elems[i]).parent().next().next().empty();
            $(elems[i]).parent().next().next().append(data);
            i++;//debugger;
            if(i <= dlina){
                send_aj(elems,disp,i,dlina);
            }



        }

        //complete: $(el).attr({'disabled':false})


    });


}

function set_all_filters(el){
    search_type('all',el);
    search_status('all',el);


}


function add_time_tab(el,id){
    var ttab = $('#ttab').val();
    $.ajax({
        type:'post',
        url: URL_ + 'ajax.php',
        data: {finery_tab:id,ttab:ttab},
        success:function(data){
            if(data == 1){
                $('#added_nar').after('<div class="alert alert-dismissible alert-success">Был обновлен T<sub>таб</sub>, установлено занчение: '+ttab+'</div>');
            }else{
                $('#added_nar').after('<div class="alert alert-dismissible alert-danger">Не удалось обновить данные, возможно, Вы вводите их повторно</div>');
            }
        },
        error:function(){
            alert('Возникла непредвиденная ошибка');
        }

    })
}
//Удаление рабочего
function del_worker(fio,id,el){

    id = parseInt(id);
    $.ajax({
        type:'post',
        success:function(data){
            if(data == 1){
                $('ul.list-group').after('<div class="alert alert-dismissible alert-success">Рабочий '+fio+' удален!</div>');
                $(el).parent().remove();
                //alert(fio);
            }else{
                $('ul.list-group').after('<div class="alert alert-dismissible alert-danger">Не удалось удалить рабочего</div>');
            }
        },
        url:URL_ + 'ajax.php',
        data: {del_work:id}
    })

}
function date_to_unixtimestamp(data){
    data = data.split(".");
    let newDate=data[2]+"-"+data[1]+"-"+data[0];
    return new Date(newDate).getTime()/1000;
}

function doctitle_to_docid(data) {

    return 1;
}

function caller(data, func) {
    switch (func) {
        case 'date_to_unixtimestamp':
            return date_to_unixtimestamp(data);
        case 'doctitle_to_docid':
            return doctitle_to_docid(data);
    }
}

//Сюда отправляется ближайший child для td
//В data-ModdedVal добавляется ожидаемое значение
function closer(_this, data, result){
    if( _this.hasClass('datetimepicker') ){
        _this.find('i').click();
    }
    if (result === 'okay') {
        _this.closest('td').html( data['ModdedVal'] );
    } else {
        _this.closest('td').html( data['prevdata'] );
    }
}

function saver(_this){
    _this.closest('td').data('ModdedVal', _this.val())
}
function sender(sqldata, data){
    let result;
    $.ajax({
        url: '../js/report_system_ajax.php',
        type: 'POST',
        dataType: 'html',
        data: {sqldata, data},
        async: false,
        success: function (echo) {
            result = echo;
        },
        error: function(echo) {
            alert(echo);
            result = echo;
        }
    });
    return result;
}
function print_r(arr, level) {
    let print_red_text = "";
    if (!level) level = 0;
    let level_padding = "";
    for (let j = 0; j < level + 1; j++) level_padding += "&nbsp&nbsp&nbsp&nbsp";
    if (typeof(arr) === 'object') {
        for (let item in arr) {
            let value = arr[item];
            if (typeof(value) === 'object') {
                print_red_text += level_padding + "'" + item + "' :<br/>";
                print_red_text += print_r(value, level + 1);
            }
            else
                print_red_text += level_padding + "'" + item + "' => \"" + value + "\"<br/>";
        }
    }
    else print_red_text = "===>" + arr + "<===(" + typeof(arr) + ")";
    return print_red_text;
}

$(document).on('dblclick', '.changelink', function (e) {
    _this = $(this);
    if( _this.children('.event').length === 0 ){
        let HTMLcontent = _this.html();             //Забираем текст
        _this.data('prevdata', HTMLcontent);        //Сохраняем изначальное содержимое в дата-параметр
        _this.html('');                             //Очищаем содержимое
        let TypeOfContent = _this.data('type');     //Получаем тип новой HTML-сущности

        let HtmlViewContent =   '<' +
            TypeOfContent
            + ' class="form-control event"/>'; //Создаем HTML-сущность

        _this.append(HtmlViewContent);
        _this.find(TypeOfContent).eq(0).focus().val(HTMLcontent);
    }
});

$(document).on('dblclick', '.changetext', function (e) {
    _this = $(this);
    if( _this.children('.event').length === 0 ){
        let HTMLcontent = _this.html();             //Забираем текст
        _this.data('prevdata', HTMLcontent);        //Сохраняем изначальное содержимое в дата-параметр
        _this.html('');                             //Очищаем содержимое
        let TypeOfContent = _this.data('type');     //Получаем тип новой HTML-сущности
        if( TypeOfContent === 'textarea' || TypeOfContent === 'input'){
            let HtmlViewContent =   '<' +
                TypeOfContent
                + ' class="form-control event"/>'; //Создаем HTML-сущность

            _this.append(HtmlViewContent);
            _this.find(TypeOfContent).eq(0).focus().val(HTMLcontent);
        }
        if( TypeOfContent === 'datetimepicker' ){
            _this.append(
                '<div class="form-group picker">' +
                '   <div class="input-group event datetimepicker">          ' +
                '      <input placeholder="Дата"                            ' +
                '             type="text"                                   ' +
                '             class="form-control"/>                        ' +
                '      <span  class="input-group-addon">                    ' +
                '             <i class="glyphicon glyphicon-calendar"/>     ' +
                '      </span>' +
                '   </div>'+
                '</div>'
            );
            _this.find('.event').datetimepicker({
                language: 'ru',
                orientation: 'left',
                changeMonth: true,
                autoclose : true,
                changeYear: true,
                format: 'DD.MM.YYYY',
                dateFormat : 'dd.mm.yy',
                defaultDate: HTMLcontent
            });
        }
    }
});

$(document).on('change', '.datetimepicker', function (e) {
    let _this = $(this);
    saver( $(this).find("input") );
    let data = _this.closest('td').data();

    let arraydate = $(this).find("input").val().split('.');
    let sqldata = new Date( arraydate[1]+'.'+arraydate[0]+'.'+arraydate[2] ).getTime() / 1000;
    let result = sender(sqldata, data);
    closer( _this, data, result);
});

$(document).on('keydown', '.event', function (e) {
    let _this = $(this);
    saver(_this);
    let data = _this.parent().data();
    let sqldata = _this.val();
    if(e.keyCode === 27){
        _this.parent().html(data['prevdata']);
        return;
    }
    if( e.ctrlKey && e.keyCode === 13 ){
        if( data['function'] ){
            sqldata = caller(sqldata, data['function']);
        }
        let result = sender(sqldata, data);
        closer(_this, data, result);
        return false;
    }
});