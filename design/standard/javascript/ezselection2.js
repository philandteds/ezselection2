YAHOO.namespace ("eZPublish.eZSelection2"); 

YAHOO.eZPublish.eZSelection2.createDataTable = function ( id, data )
{
    var sortKey = function(a, b, desc) 
    {
	// Deal with empty values
	if(!YAHOO.lang.isValue(a)) {
	    return (!YAHOO.lang.isValue(b)) ? 0 : 1;
	}
	else if(!YAHOO.lang.isValue(b)) {
	    return -1;
	}

        var columnKey = YAHOO.eZPublish.eZSelection2.sortingBy.column.key;
        if (columnKey.length == 0)
        {
            columnKey = "name";
	}

	var comp = YAHOO.util.Sort.compare;
	var compState = comp(a.getData(columnKey), b.getData(columnKey), desc);

	return (compState !== 0) ? compState : comp(a.getData(columnKey), b.getData(columnKey), desc);
    };
    
    var columnDefinition =
    [
        { key:"name", label:'Name', sortable: true, sortOptions:{sortFunction:sortKey}, formatter: function (el, oRecord, oColumn, oData) 
            {
	        el.innerHTML = '<input size="10" value="' + oData + '" class="ezselection2_input" name="ContentClass_ezselection2_name_' + id +'[]" /> '; 
            }
        },

        { key:"identifier", label:'Identifier', sortable: true, sortOptions:{sortFunction:sortKey}, formatter: function (el, oRecord, oColumn, oData) 
            {
	        el.innerHTML = '<input value="' + oData + '"  class="ezselection2_input" name="ContentClass_ezselection2_identifier_' + id +'[]" /> '; 
            }
        },

        { key:"value", label:'Selected', sortable: false, formatter: function (el, oRecord, oColumn, oData) 
            {
	        el.innerHTML = '<input value="' + oData + '"  class="ezselection2_input" name="ContentClass_ezselection2_value_' + id +'[]" /> '; 
            }
        },

        { key:"moveup", label:'', sortable:false, resizeable:false, formatter: function(el)
            {
                el.innerHTML = ' <img src="/design/admin/images/button-move_up.gif" title="Move row up" /> ';
                el.style.cursor = 'pointer'
            }
	},

        { key:"movedown", label:'', sortable:false, resizeable:false, formatter: function(el)
            {
                el.innerHTML = ' <img src="/design/admin/images/button-move_down.gif" title="Move row down" /> ';
                el.style.cursor = 'pointer'
            }
	},

        { key:"remove", label:'', sortable:false, resizeable:false, formatter: function(el)
            {
                el.innerHTML = ' <img src="/design/admin/images/content_tree-error.gif" title="Delete Row" /> ';
                el.style.cursor = 'pointer'
            }
        }
    ];
    
    var dataSource = new YAHOO.util.DataSource(data);
    dataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRY; 
    dataSource.responseSchema = { fields: ["name","identifier","value"] };

    var optionDataTable = new YAHOO.widget.DataTable("option-table-"+id,
                                                     columnDefinition,
                                                     dataSource);

    /*
    var inputs = YAHOO.util.Dom.getElementsByClassName('ezselection2_input', 'input');
    for (var i=0; i < inputs.length; i++) 
    {
        inputField = inputs[i];
        YAHOO.util.Event.addListener( inputField, "change", function(e)
        {
            var input = YAHOO.util.Event.getTarget(e);
            var cell = YAHOO.util.Dom.getAncestorByTagName(input, "td");
            var record =optionDataTable.getRecord(cell);
            var column =optionDataTable.getColumn(cell);

	    record.setData(column.key, input.value);
	    alert(YAHOO.lang.dump(record.getData()));
        });
    }
    */

    var mySortFunction = function(a, b, desc) 
    {
	alert(YAHOO.lang.dump(a.getData()));

	// Deal with empty values
	if(!YAHOO.lang.isValue(a)) {
	    return (!YAHOO.lang.isValue(b)) ? 0 : 1;
	}
	else if(!YAHOO.lang.isValue(b)) {
	    return -1;
	}

	// First compare by Column2
	var comp = YAHOO.util.Sort.compare;
	var compState = comp(a.getData("name"), b.getData("name"), desc);

	// If values are equal, then compare by Column1
	return (compState !== 0) ? compState : comp(a.getData("name"), b.getData("name"), desc);
    };

     YAHOO.util.Event.on(optionDataTable.getTbodyEl(),'keypress',function (e) 
     {
         var input = YAHOO.util.Event.getTarget(e);
	 if (input.tagName.toUpperCase() == 'INPUT') 
         {
	     var cell = YAHOO.util.Dom.getAncestorByTagName(input,'td');

             var record = optionDataTable.getRecord(input);
	     var column = optionDataTable.getColumn(input);

             record.setData(column.key, input.value);
         }
     });

    optionDataTable.subscribe('cellClickEvent', function(oArgs) 
    {
        var target = oArgs.target;
	var column = this.getColumn(target);
	var record = this.getRecord(target);
        var recordIndex = this.getRecordIndex(record);

	switch(column.key) 
        {
            case 'remove':

                this.deleteRow(target);
                break;

            case 'moveup':

                if (recordIndex > 0)
                {
                    previousRecord = this.getRecord( recordIndex - 1);

                    recordData = record.getData();
                    previousRecordData = previousRecord.getData();

                    this.updateRow(record, previousRecordData);
                    this.updateRow(previousRecord, recordData);
		}                
                break; 

            case 'movedown':

                recordSet = this.getRecordSet();
                recordSetCount = recordSet.getRecords().length;

                if (recordIndex < recordSetCount)
                {
                    nextRecord = this.getRecord( recordIndex + 1);

                    recordData = record.getData();
                    nextRecordData = nextRecord.getData();

                    this.updateRow(record, nextRecordData);
                    this.updateRow(nextRecord, recordData);
		}                
                break; 
	}
    });

    /*
   optionDataTable.on('cellMouseoutEvent', function(e)
    {
        
        var cell = YAHOO.util.Event.getTarget(e);
	var childDiv = YAHOO.util.Dom.getChildrenBy( cell, function(e){return (e.tagName == "DIV")})[0];
	var input = YAHOO.util.Dom.getChildrenBy( childDiv, function(e){return (e.tagName == "INPUT")})[0];

        var record = optionDataTable.getRecord(cell);
        var column = optionDataTable.getColumn(cell);
        record.setData(column.key, input.value);
	});*/


    optionDataTable.doBeforeSortColumn = function (col,dir) 
    {
        YAHOO.eZPublish.eZSelection2.sortingBy = 
        {
	    column: col,
	    direction:dir
	}

	return true;
    }

    var pushButton = new YAHOO.widget.Button("AddRow_"+id); 
    pushButton.on("click", function(e) 
    {
	//	alert(YAHOO.lang.dump(optionDataTable.getRecord(1).getData()));
        optionDataTable.addRow( { "name":"", "identifier":"", "value":""});
    });
}



YAHOO.widget.DataTable.prototype.getTdEl = function(cell) {

    var Dom = YAHOO.util.Dom,

    lang = YAHOO.lang,
    elCell,
    el = Dom.get(cell);

    // Validate HTML element
    if(el && (el.ownerDocument == document)) {

        // Validate TD element
        if(el.nodeName.toLowerCase() != "td") {

            // Traverse up the DOM to find the corresponding TR element
            elCell = Dom.getAncestorByTagName(el, "td");

        }
        else {

            elCell = el;

        }

        // Make sure the TD is in this TBODY
        if(elCell && (elCell.parentNode.parentNode == this._elTbody)) {

            // Now we can return the TD element
            return elCell;

        }

    }
    else if(cell) {

        var oRecord, nColKeyIndex;

        if(lang.isString(cell.columnKey) && lang.isString(cell.recordId)) {

            oRecord = this.getRecord(cell.recordId);
            var oColumn = this.getColumn(cell.columnKey);
            if(oColumn) {

                nColKeyIndex = oColumn.getKeyIndex();

            }

        }
        if(cell.record && cell.column && cell.column.getKeyIndex) {

            oRecord = cell.record;
            nColKeyIndex = cell.column.getKeyIndex();

        }
        var elRow = this.getTrEl(oRecord);
        if((nColKeyIndex !== null) && elRow && elRow.cells && elRow.cells.length > 0) {

            return elRow.cells[nColKeyIndex];

        }

    }

    return null;

};
