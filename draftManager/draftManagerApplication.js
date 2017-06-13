Ext.namespace("draftManager");

draftManager.application = {
  init: function () {
    storeCaseProcess = function (n, r, i) {
      var myMask = new Ext.LoadMask(Ext.getBody(), { msg: "Load cases..." });
      myMask.show();

      Ext.Ajax.request({
        url: "draftManagerApplicationAjax",
        method: "POST",
        params: { "option": "LST", "pageSize": n, "limit": r, "start": i, "process": "" },

        success: function (result, request) {
          storeCase.loadData(Ext.util.JSON.decode(result.responseText));
          //console.log(result.responseText);
          myMask.hide();
        },
        failure: function (result, request) {
          myMask.hide();
          Ext.MessageBox.alert("Alert", "Failure cases load");
        }
      });
    };

    onMnuContext = function (grid, rowIndex, e) {
      e.stopEvent();
      var coords = e.getXY();
      mnuContext.showAt([coords[0], coords[1]]);
    };

    //Variables declared in html file
    var pageSize = parseInt(CONFIG.pageSize);
    var message = CONFIG.message;

    //stores
    var storeCase = new Ext.data.Store({
      proxy: new Ext.data.HttpProxy({
        url: "draftManagerApplicationAjax",
        method: "POST"
      }),

      //baseParams: {"option": "LST", "pageSize": pageSize},

      reader: new Ext.data.JsonReader({
        root: "data",
        totalProperty: "total",
        fields: [{ name: "app_uid" },
        { name: "app_number" },
        { name: "app_title" },
        { name: "app_pro_title" },
        { name: "app_tas_title" },
        { name: "app_current_user" },
        { name: "app_update_date" },
        { name: "app_status_label" }
        ]
      }),

      //autoLoad: true, //First call

      listeners: {
        beforeload: function (store) {
          this.baseParams = { "option": "LST", "pageSize": pageSize, "process": suggestProcess.value };
        }
      }
    });

    var processStore = new Ext.data.Store({
      proxy: new Ext.data.HttpProxy({
        url: 'draftManagerApplicationAjax',
        method: 'POST'
      }),

      reader: new Ext.data.JsonReader({
        root: "data",
        fields: [{
          name: 'prj_uid'
        }, {
          name: 'prj_name'
        }]
      }),

      listeners: {
        beforeload: function (store) {
          this.baseParams = { "option": "PRO" };
        }
      }
    });

    var storePageSize = new Ext.data.SimpleStore({
      fields: ["size"],
      data: [["15"], ["25"], ["35"], ["50"], ["100"]],
      autoLoad: true
    });

    //
    var btnSelect = new Ext.Action({
      id: "btnSelect",

      text: "Select All",
      iconCls: "button_menu_ext ss_sprite ss_table_add",

      handler: function () {
        grdpnlCase.getSelectionModel().selectAll();
        //Ext.MessageBox.alert("Alert", message);
      }
    });

    var btnClear = new Ext.Action({
      id: "btnClear",

      text: "Clear Selected",
      iconCls: "button_menu_ext ss_sprite ss_table_delete",
      disabled: true,

      handler: function () {
        //Ext.MessageBox.alert("Alert", message);
        grdpnlCase.getSelectionModel().clearSelections();
      }
    });

    var btnCancel = new Ext.Action({
      id: "btnCancel",

      text: "Cancel Selected Cases",
      iconCls: "button_menu_ext ss_sprite ss_cancel",
      disabled: true,

      handler: function () {
        //Ext.MessageBox.alert("Alert", message);
        var rows = grdpnlCase.getSelectionModel().getSelections();
        var cases = rows.map(function (row) {
          return row.data;
        })
        cancelCases(JSON.stringify(cases));
      }
    });

    

    var mnuContext = new Ext.menu.Menu({
      id: "mnuContext",

      items: [btnClear, btnCancel]
    });


    var cboPageSize = new Ext.form.ComboBox({
      id: "cboPageSize",

      mode: "local",
      triggerAction: "all",
      store: storePageSize,
      valueField: "size",
      displayField: "size",
      width: 50,
      editable: false,

      listeners: {
        select: function (combo, record, index) {
          pageSize = parseInt(record.data["size"]);

          pagingUser.pageSize = pageSize;
          pagingUser.moveFirst();
        }
      }
    });

    var pagingUser = new Ext.PagingToolbar({
      id: "pagingUser",

      pageSize: pageSize,
      store: storeCase,
      displayInfo: true,
      displayMsg: "Displaying cases " + "{" + "0" + "}" + " - " + "{" + "1" + "}" + " of " + "{" + "2" + "}",
      emptyMsg: "No cases to display",
      items: ["-", "Page size:", cboPageSize]
    });

    var cmodel = new Ext.grid.ColumnModel({
      defaults: {
        width: 50,
        sortable: true
      },
      columns: [{ id: "App UID", dataIndex: "app_uid", hidden: true },
      { header: "#", dataIndex: "app_number", width: 5, align: "center" },
      { header: "Case", dataIndex: "app_title", width: 20, align: "left" },
      { header: "Process", dataIndex: "app_pro_title", width: 20, align: "left" },
      { header: "Task", dataIndex: "app_tas_title", width: 20, align: "left" },
      { header: "User", dataIndex: "app_current_user", width: 15, align: "left" },
      { header: "Last Update Date", dataIndex: "app_update_date", width: 10, align: "left" },
      { header: "Status", dataIndex: "app_status_label", width: 10, align: "left" }
      ]
    });

    var smodel = new Ext.grid.RowSelectionModel({
      singleSelect: false,
      listeners: {
        rowselect: function (sm) {
          btnClear.enable();
          btnCancel.enable();
        },
        rowdeselect: function (sm) {
          btnClear.disable();
          btnCancel.disable();
        }
      }
    });

    var suggestProcess = new Ext.form.ComboBox({
      store: processStore,
      valueField: 'prj_uid',
      displayField: 'prj_name',
      typeAhead: false,
      triggerAction: 'all',
      emptyText: _('ID_EMPTY_PROCESSES'),
      selectOnFocus: true,
      editable: true,
      width: 150,
      allowBlank: true,
      autocomplete: true,
      minChars: 1,
      hideTrigger: true,
      listeners: {
        scope: this,
        'select': function () {
          filterProcess = suggestProcess.value;
          console.log(filterProcess);
          storeCase.setBaseParam('process', filterProcess);
          doSearch();
        }
      }
    });

    var resetProcessButton = {
      text: 'X',
      ctCls: "pm_search_x_button_des",
      handler: function () {
        storeCase.setBaseParam('process', '');
        suggestProcess.setValue('');
        doSearch();
      }
    };

    var btnSearch = new Ext.Button({
      text: _('ID_SEARCH'),
      iconCls: 'button_menu_ext ss_sprite ss_page_find',
      //cls: 'x-form-toolbar-standardButton',
      handler: doSearch
    });

    function doSearch() {
      storeCase.load({ params: { start: 0, limit: pageSize } });
    }

    function cancelCases(selectedCases) {
      var myMask = new Ext.LoadMask(Ext.getBody(), { msg: "Cancelling cases..." });
      myMask.show();

      Ext.Ajax.request({
        url: "draftManagerApplicationAjax",
        method: "POST",
        params: { "option": "CNL", "cases": selectedCases },

        success: function (result, request) {
          var resp = JSON.parse(result.responseText);
          var respString = "";
          resp.data.forEach(function(c) {
            respString += "Case:" + c.app_number + " - " + c.cancel_result + "<br>";
          });
          Ext.MessageBox.alert("Cancel Request Result", respString);
          doSearch();
          myMask.hide();
        },
        failure: function (result, request) {
          myMask.hide();
          Ext.MessageBox.alert("Alert", "Failure cases load");
        }
      });
    };



    var grdpnlCase = new Ext.grid.GridPanel({
      id: "grdpnlCase",

      store: storeCase,
      colModel: cmodel,
      selModel: smodel,

      columnLines: true,
      viewConfig: { forceFit: true },
      enableColumnResize: true,
      enableHdMenu: true, //Menu of the column

      tbar: [btnSelect, "-", btnClear, btnCancel, "-", "->", suggestProcess, resetProcessButton],
      bbar: pagingUser,

      style: "margin: 0 auto 0 auto;",
      //width: 550,
      //height: 450, 
      autoHeight: true,
      title: "Manage Draft Cases",

      renderTo: "divMain",

      listeners: {
      }
    });

    //Initialize events
    storeCaseProcess(pageSize, pageSize, 0);

    grdpnlCase.on("rowcontextmenu",
      function (grid, rowIndex, evt) {
        var sm = grid.getSelectionModel();
        sm.selectRow(rowIndex, sm.isSelected(rowIndex));
      },
      this
    );

    grdpnlCase.addListener("rowcontextmenu", onMnuContext, this);

    cboPageSize.setValue(pageSize);
  }
}

Ext.onReady(draftManager.application.init, draftManager.application);