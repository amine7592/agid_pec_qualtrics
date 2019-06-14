Qualtrics.SurveyEngine.addOnload(function() {



    window.saveL = function() {
        function datenum(v, date1904) {
            if (date1904) v += 1462;
            var epoch = Date.parse(v);
            return (epoch - new Date(Date.UTC(1899, 11, 30))) / (24 * 60 * 60 * 1000);
        }

        function sheet_from_array_of_arrays(data, opts) {
            //console.log("ciao1")
            var ws = {};
            var range = {
                s: {
                    c: 10000000,
                    r: 10000000
                },
                e: {
                    c: 0,
                    r: 0
                }
            };
            for (var R = 0; R != data.length; ++R) {
                for (var C = 0; C != data[R].length; ++C) {
                    if (range.s.r > R) range.s.r = R;
                    if (range.s.c > C) range.s.c = C;
                    if (range.e.r < R) range.e.r = R;
                    if (range.e.c < C) range.e.c = C;
                    var cell = {
                        v: data[R][C]
                    };
                    if (cell.v == null) continue;
                    var cell_ref = XLSX.utils.encode_cell({
                        c: C,
                        r: R
                    });

                    if (typeof cell.v === 'number') cell.t = 'n';
                    else if (typeof cell.v === 'boolean') cell.t = 'b';
                    else if (cell.v instanceof Date) {
                        cell.t = 'n';
                        cell.z = XLSX.SSF._table[14];
                        cell.v = datenum(cell.v);
                    } else cell.t = 's';
                    cell.s = {
                        border: {
                            // top:{
                            // style: "thin", 
                            // color: { rgb: "000000" }
                            // },
                            // left:{
                            // style: "thin", 
                            // color: { rgb: "000000" }
                            // },
                            // right:{
                            // style: "thin", 
                            // color: { rgb: "000000" }
                            // },
                            // bottom:{
                            // style: "thin", 
                            // color: { rgb: "000000" } 
                            // }
                        },

                        font: {
                            name: 'Titillium Web',
                            size: 16,
                        }
                    }

                    if (R == 0) {
                        cell.s = {
                            font: {
                                size: 18,
                                bold: true,
                                color: {
                                    argb: 'FF0059B3'
                                },
                            }
                        }
                    }
                    if (C == 0) {
                        cell.s = {
                            font: {
                                bold: true,
                            },
                            // fill: {
                            //     fgColor: { rgb: "acacac" }
                            // }
                        }
                    }


                    ws[cell_ref] = cell;
                }
            }
            if (range.s.c < 10000000) ws['!ref'] = XLSX.utils.encode_range(range);
            return ws;
        }

        function Workbook() {
            if (!(this instanceof Workbook)) return new Workbook();
            this.SheetNames = [];
            this.Sheets = {};
        }

        function s2ab(s) {
            var buf = new ArrayBuffer(s.length);
            var view = new Uint8Array(buf);
            for (var i = 0; i != s.length; ++i) view[i] = s.charCodeAt(i) & 0xFF;
            return buf;
        }


        //var data = [];
        var values1 = [];
        var values2 = [];

        var ente = $("#QID9 .QuestionText")[0].innerText



        // // // // // // // // NOTE a03
        var notea03 = $("#QID619 input")[0].value
        // console.log(note)

        // // // // // // // // NOTE a05
        var notea05 = $("#QID620 input")[0].value
        // console.log(note)

        // // // // // // // // SELECT a03
        var select03 = $("#QID47 ul li .LabelWrapper label");

        var stringChecka03 = " "
        Array.prototype.forEach.call(select03, function(el03) {
            function hasClass(element03, cls03) {
                return (' ' + element03.className + ' ').indexOf(' ' + cls03 + ' ') > -1;
            }

            if (hasClass(el03, 'q-checked') == true) {
                stringChecka03 += $("#QID47 .q-checked span")[0].innerText
                //console.log();
            }
        });

        // // // // // // // // SELECT a04
        var select04 = $("#QID5 ul li .LabelWrapper label");
        var stringChecka04 = " "
        Array.prototype.forEach.call(select04, function(el04) {
            function hasClass(element04, cls04) {
                return (' ' + element04.className + ' ').indexOf(' ' + cls04 + ' ') > -1;
            }

            if (hasClass(el04, 'q-checked') == true) {
                stringChecka04 += $("#QID5 .q-checked span")[0].innerText
            }
        });
        // // // // // // // // SELECT a05
        //  var select05 = $(".QR-QID595 option");  
        //   var stringSelect05 = " "
        // Array.prototype.forEach.call(select05, function(el05) { 
        //     function hasClass(element05, cls05) {
        //     return (' ' + element05.className + ' ').indexOf(' ' + cls05 + ' ') > -1;
        // }

        // if( .QR-QID595 option:selected == true){
        //     console.log()
        //     stringSelect05 += $(".q-checked span")[1].innerText
        // }
        // });

        //  var stringSelect05 = $(".QR-QID595 option:selected")[0].innerText;



        var select05 = $("#QID595 ul li .LabelWrapper label");

        var stringChecka05 = " "
        Array.prototype.forEach.call(select05, function(el05) {
            function hasClass(element05, cls05) {
                return (' ' + element05.className + ' ').indexOf(' ' + cls05 + ' ') > -1;
            }

            if (hasClass(el05, 'q-checked') == true) {
                stringChecka05 += $("#QID595 .q-checked span")[0].innerText
                //console.log();
            }
        });

        // // // // // // // // TABELLA
        var inputs1 = jQuery("#QID2 input[type='text']");

        inputs1
            .toArray()
            .forEach(function(field) {
                if (field.value == '') {
                    field_val = 0;
                } else if (isNaN(parseInt((field.value).replace(/,/g, '')))) {
                    console.log("note")
                    field_val = field.value;
                } else {
                    console.log("numero")
                    field_val = parseInt((field.value).replace(/,/g, ''));
                }

                values1.push(field_val)
            })


        var RowTitleSurvey = ['A. Informazioni Generali']
        var RowBlank = [' ']
        var Rowsub = ['All’interno di questa sezione si richiedono le informazioni disponibili al 31/12/2017 e, se possibile anche per il triennio 2018-2020, relative alla dimensione dell’Ente e alla modalità di gestione dei sistemi informativi.​']
        var RowBlank = [' ']
        var RowBlank = [' ']
        var RowTitle1 = [ente]
        var RowBlank = [' ']
        var RowBlank = [' ']
        var RowBlank = [' ']
        var RowTitle2 = ['A02. Modalità prevalente di gestione dei sistemi informativi a fine anno 2017']
        var RowBlank = [' ']
        var Row0 = ["", 2016, 2017, 2018, 2019, 2020, "Note"]
        var Row1 = ['Personale in servizio']
        var Row2 = ['Dipendenti ICT']
        var Row3 = ['Personale non dipendente assegnato ai servizi ICT']
        var Row4 = ['Numero sedi']
        var Row5 = ['Numero sedi collegate alle rete della sede centrale']
        var Row6 = ['Servizi digitali']
        var RowBlank = [' ']
        var RowBlank = [' ']
        var RowTitle3 = ['A03. Modalità prevalente di gestione sistemi informativi a fine 2018']
        var Row7 = [stringChecka03]
        var RowBlank = [' ']
        var RowBlank = [' ']
        var Row8 = ['Note']
        var Row9 = [notea03]
        var RowBlank = [' ']
        var RowBlank = [' ']
        var Row10 = ['A04. Modalità di gestione dei sistemi informativi per le successive annualità 2019 e 2020 indicare la nuova']
        var Row11 = [stringChecka04]
        var RowBlank = [' ']
        var RowBlank = [' ']
        var Row12 = ['A05. Modalità di gestione dei sistemi informativi per le successive annualità 2019 e 2020']
        var Row13 = [stringChecka05]
        var RowBlank = [' ']
        var RowBlank = [' ']
        var Row14 = ['Note']
        var Row15 = [notea05]

        var Row1v = values1.slice(0, 6);
        var Row2v = values1.slice(6, 12);
        var Row3v = values1.slice(12, 18);
        var Row4v = values1.slice(18, 24);
        var Row5v = values1.slice(24, 30);
        var Row6v = values1.slice(30, 36);
        Row1 = Row1.concat(Row1v);
        Row2 = Row2.concat(Row2v);
        Row3 = Row3.concat(Row3v);
        Row4 = Row4.concat(Row4v);
        Row5 = Row5.concat(Row5v);
        Row6 = Row6.concat(Row6v);

        /* original data */
        data1 = [RowTitleSurvey, RowBlank, RowBlank, RowTitle1, RowBlank, RowTitle2, Row0, Row1, Row2, Row3, Row4, Row5, Row6, RowBlank, RowBlank, RowTitle3, Row7, RowBlank, RowBlank, Row8, Row9, RowBlank, RowBlank, Row10, Row11, RowBlank, RowBlank, Row12, Row13, RowBlank, RowBlank, Row14, Row15];
        data2 = [RowTitleSurvey, RowBlank, RowTitle1, Row0, Row1, Row2, Row3, Row4, Row5, Row6];

        //verify A04 true
        let arr_to_hide = [Row12, Row13, Row14, Row15];
        let true_choice = document.getElementById('QID5-1-label');
        if (true_choice.classList.contains('q-checked') == false) {
            //console.log('Non presente');
            for (var i = 0; i < data1.length; i++) {
                for (var j = 0; j < arr_to_hide.length; j++) {
                    if (data1[i] === arr_to_hide[j]) {
                        data1.splice(i, 1);
                        i--;
                    }
                }
            }
        }

        var blob, wb = {
                SheetNames: [],
                Sheets: {}
            },
            ws1 = sheet_from_array_of_arrays(data1);
        //    var blob, wb = {SheetNames:[], Sheets:{}}, ws1 = sheet_from_array_of_arrays(data1), ws2 = sheet_from_array_of_arrays(data2);

        if (!ws1['!merges']) ws1['!merges'] = [{
                s: {
                    r: 0,
                    c: 0
                },
                e: {
                    r: 0,
                    c: 6
                }
            },
            {
                s: {
                    r: 3,
                    c: 0
                },
                e: {
                    r: 3,
                    c: 6
                }
            }
        ];

        // if(!ws2['!merges']) ws2['!merges'] = [ 
        //             { s: {r:0, c:0}, e: {r:0, c:6} },
        //             { s: {r:3, c:0}, e: {r:3, c:6} }
        //         ];



        /* add worksheet to workbook */
        wb.SheetNames.push("Sezione A");
        wb.Sheets["Sezione A"] = ws1;
        // wb.SheetNames.push("Sheet2"); wb.Sheets["Sheet2"] = ws2;
        var wbout = XLSX.write(wb, {
            bookType: 'xlsx',
            bookSST: true,
            type: 'binary'
        });
        saveAs(new Blob([s2ab(wbout)], {
            type: "application/octet-stream"
        }), "Rilevazione Spesa ICT Sezione A.xlsx")

    }

    function read() {
        /* set up XMLHttpRequest */
        var url = "Rilevazione Spesa ICT Sezione A.xlsx";
        var oReq = new XMLHttpRequest();
        oReq.open("GET", url, true);
        oReq.responseType = "arraybuffer";
        oReq.onload = function(e) {
            var arraybuffer = oReq.response;
            /* convert data to binary string */
            var data = new Uint8Array(arraybuffer);
            var arr = new Array();
            for (var i = 0; i != data.length; ++i) arr[i] = String.fromCharCode(data[i]);
            var bstr = arr.join("");
            /* Call XLSX */
            var workbook = XLSX.read(bstr, {
                type: "binary"
            });
            //console.log(workbook);
            /* DO SOMETHING WITH workbook HERE */
            var first_sheet_name = workbook.SheetNames[0];
            var address_of_cell = 'A1';
            /* Get worksheet */
            var worksheet = workbook.Sheets[first_sheet_name];

            /* Find desired cell */
            var desired_cell = worksheet[address_of_cell];
            /* Get the value */
            var desired_value = desired_cell.v;


            var wb = new Workbook(),
                ws = worksheet;

            /* add worksheet to workbook */


            wb.SheetNames.push("new");
            wb.Sheets["new"] = ws;
            var wbout = XLSX.write(wb, {
                bookType: 'xlsx',
                bookSST: true,
                type: 'binary'
            });
            saveAs(new Blob([s2ab(wbout)], {
                type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
            }), "Rilevazione Spesa ICT Sezione A.xlsx")
        }
        oReq.send();
    }




    // var newNode = $("#PreviousButton").after(' <a id="some-link" onclick="saveL()" style="cursor:pointer"><span>DOWNLOAD</span><span><i style="transform: scale(1.5); margin-left:10px" class="far fa-file-excel"></i></span></a>');
    var newNodeSezione = $("#TocSidebarContainer").after('<div class="cont-access"><div id="menu-fixed" onclick="saveL()" onmouseout="chiuditi()" onmouseover="apriti()"><div class="img-excel"><i class="far fa-file-excel"></i></div><p class="text-excel">Download Excel</p></div></div> ');



});



Qualtrics.SurveyEngine.addOnReady(function() {


    
    var element, name, arr;
    element = document.getElementById("TocSidebarContainer");
    name = "closed";
    arr = element.className.split(" ");
    if (arr.indexOf(name) == -1) {
        element.className += " " + name;
    }
    element.style.display = "block"
    element.style.left = "-380px";
    /*Place your JavaScript here to run when the page is fully displayed*/
    
    var getQueryVariable = function(variable) {
        var query = window.location.search.substring(1);
        var vars = query.split("&");
        for (var i=0;i<vars.length;i++) {
            var pair = vars[i].split("=");
            if (pair[0] == variable) {
              return pair[1];
            }
        } 
        return variable;
    };

    var encrypted = getQueryVariable("sid").replace("ewsdcgfhvg","");
    console.log(encrypted);
    
    var usr = JSON.parse(atob(encrypted));
    
    console.log(usr);
    
    var fiscalnumber = (usr.fiscalnumber.indexOf("TINIT") == -1 ) ? ("TINIT-" + usr.fiscalnumber) : usr.fiscalnumber; 
    console.log(fiscalnumber);
    
    $("#USRINFO ").text(usr.name + " " + usr.familyname + " " + fiscalnumber + " " + usr.email);


});


Qualtrics.SurveyEngine.addOnUnload(function() {
    /*Place your JavaScript here to run when the page is unloaded*/

});