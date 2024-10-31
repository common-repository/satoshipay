;(function($, document, window, undefined) {

    var selectorRunButton = '#sp__runmigration';

    var migrator = {

        selectorRunButton   : '#sp__runmigration',
        selectorProgressBar : '#sp__migrationprogress',

        $runButton          : null,

        isRunning           : false,
        defaultPagesize     : 500,
        currentPage         : null,
        pagecount           : null,
        pagesize            : null,


        init : function()
        {
            migrator.$runButton = $(migrator.selectorRunButton);
            migrator.$progressbar = $(migrator.selectorProgressBar);

            migrator.bindRunButton();

            migrator.showProgressBar(100);
            migrator.hideProgressBar();
        },


        promptForConfirmation: function(callback)
        {
            var confirmationPrompt = prompt('Did you back up your posts? Some posts may break in the update process but can be recovered from a backup.\n\nPlease type UNDERSTOOD to start the script.\n');

            if (confirmationPrompt === 'UNDERSTOOD') {
                if (callback) {
                    callback.apply();
                }
                return true;
            }
        },


        reportError: function(response)
        {
            migrator.isRunning = false;

            migrator.hideProgressBar();
            migrator.showRunButton();

            alert('There was an error, the process will not continue :-(');

            console.log('There was an error, the process will not continue :-(');
            console.log('Server says: ' + response.statusText);
            console.log(response);
        },


        run: function()
        {
            if (migrator.isRunning) {
                return;
            }

            migrator.isRunning = true;

            migrator.requestPagecount(function() {

                migrator.currentPage = 1;

                migrator.requestPostsProcessing(function() {
                    migrator.isRunning = false;

                    migrator.successProgressBar(100);
                    setTimeout(function() {
                        migrator.hideProgressBar();
                        migrator.showRunButton();
                    }, 1500);

                });

            }, migrator.reportError);
        },


        ajax: function(data, onSuccess, onError, url, method)
        {
            if (undefined === url) {
                url = ajaxurl;
            }

            if (undefined === method) {
                method = 'post';
            }

            $.ajax({
                type        : method,
                dataType    : 'json',
                url         : url,
                data        : data,
                success     : function(response) {
                    if (onSuccess) {
                        onSuccess(response);
                    }
                },
                error       : function(response) {
                    if (onError) {
                        onError(response);
                    }
                }
            });
        },


        requestPagecount: function(onSuccess, onError)
        {
            var data = {
                action      : 'satoshipay-migration-countpages',
                pagesize    : migrator.defaultPagesize
            };

            migrator.currentPage    = null;
            migrator.pagecount      = null;
            migrator.pagesize       = null;

            migrator.ajax(
                data,
                function(response) {
                    migrator.showProgressBar(100);
                    migrator.hideRunButton();

                    migrator.pagecount  = response.data.pages;
                    migrator.pagesize   = response.data.pagesize;

                    if (onSuccess) {
                        onSuccess(response);
                    }
                },
                onError
            );
        },


        requestPostsProcessing: function(onFinish)
        {
            var data = {
                action      : 'satoshipay-migration-processposts',
                pagesize    : migrator.pagesize,
                page        : migrator.currentPage
            };

            console.log(data, migrator.currentPage);


            migrator.ajax(
                data,
                function(response) {
                    migrator.currentPage++;
                    console.log(data, migrator.currentPage);

                    var progress = Math.round((migrator.currentPage / migrator.pagecount) * 100);

                    migrator.showProgressBar(progress);
                    // hideActionButton();

                    if (migrator.currentPage < migrator.pagecount) {
                        setTimeout(function() {migrator.requestPostsProcessing(onFinish);}, 2);
                    } else {
                        if (onFinish) {
                            onFinish();
                        }
                    }
                },
                migrator.reportError
            );


            var processPosts = function() {

                var page = pagecount - pagesToProcess;

                var data = {
                    action      : 'satoshipay-migration-processposts',
                    pagesize    : pagesize,
                    page        : page
                };

                console.log(data);

                jQuery.ajax({
                    type        : 'post',
                    dataType    : 'json',
                    url         : ajaxurl,
                    data        : data,
                    success     : function(response) {
                        pagesToProcess--;

                        var progress = Math.round((pagesToProcess / pagecount) * 100);

                        showProgressBar(progress);
                        hideActionButton();

                        if (pagesToProcess) {
                            setTimeout(processPosts, 2);
                        } else {
                            successProgressBar(100);
                            setTimeout(function() {
                                hideProgressBar();
                                showActionButton();
                            }, 1500);
                        }
                    },
                    error       : function(response) {
                        hideProgressBar();
                        showActionButton();
                        alert('There was an error, the process will not continue :-(');
                        console.log('There was an error, the process will not continue :-(');
                        console.log('Server says: ' + responseText);
                        console.log(response);
                    }
                });
            };


        },


        bindRunButton: function()
        {
            var me = this;
            this.$runButton.on('click', function(e) {
                e.stopPropagation();
                e.preventDefault();

                if (migrator.isRunning) {
                    alert('The update is already in process, please be patient.');
                } else {
                    migrator.promptForConfirmation(migrator.run);
                }
            });
        },


        showRunButton: function()
        {
            migrator.$runButton.parent().show();
        },


        hideRunButton: function()
        {
            migrator.$runButton.parent().hide();
        },


        showProgressBar: function(size)
        {
            if (undefined === size) {
                size = 100;
            }

            size = 100 - size;

            migrator.$progressbar.parent().show();
            migrator.$progressbar.css({
                'transition'    : '0.125s width, 0.125s margin-left, 0.125s margin-right',
                'background'    : '#000',
                'color'         : '#fff',
                'text-shadow'   : '1px 1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, -1px -1px 0 #000',
                'text-align'    : 'center',
                'padding'       : '0.1rem',
                'width'         : size + '%',
                'margin-left'   : ((100 - size) / 2) + '%',
                'margin-right'  : ((100 - size) / 2) + '%'
            }).text((100 - size) + '%');

        },


        successProgressBar: function(size)
        {
            if (undefined === size) {
                size = 100;
            }

            migrator.$progressbar.parent().show();
            migrator.$progressbar.css({
                'transition'    : '0.75s width, 0.75s margin-left, 0.75s margin-right',
                'background'    : '#0c0',
                'color'         : '#fff',
                'text-shadow'   : '1px 1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, -1px -1px 0 #000',
                'text-align'    : 'center',
                'padding'       : '0.1rem',
                'width'         : size + '%',
                'margin-left'   : ((100 - size) / 2) + '%',
                'margin-right'  : ((100 - size) / 2) + '%'
            }).text((size) + '%');

        },


        hideProgressBar: function()
        {
            migrator.$progressbar.parent().hide();
        }
    };

    $(function() {
        migrator.init();
    });
})(jQuery, document, window);
