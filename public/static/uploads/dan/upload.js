function DragImgUpload(id, options) {
    this.me = $(id);
    var defaultOpt = {
        boxWidth: '200px', boxHeight: 'auto',
        uploadType: 'image', // 表示图片 image    video 视频
        imageObj: {
            previewHtml: '<img src="img/upload.png" class="img-responsive"  style="width: 100%;height: auto;" alt="" title=""> ',
            size: 1024 * 5, // 上传的大小限制 默认是 5 mb
            type: 'image', // 上传的文件类型
            errotMsg: '只允许图片类型',
            hideClass: '.drop-upload-img',
        },
        videoObj: {
            previewHtml: '<video style="width: 100%;height: auto; display: none;" controls></video>',
            size: 30 * 1024, // 上传的大小限制 默认是 30mb
            type: 'video', // 上传的文件类型
            errotMsg: '只允许视频类型',
            hideClass: '.drop-upload-video',
        },
    }
    this.opts = $.extend(true, defaultOpt, {}, options);
    var objKey = this.opts.uploadType + 'Obj';
    this.opts.key = objKey;
    this.previewUploadDom = $(this.opts[this.opts.key].previewHtml);
    this.preview = $('<div id="previewShare" class="dragUpload-view"></div>');
    this.preview.append(this.previewUploadDom);
    this.init();
    this.callback = this.opts.callback;
}

DragImgUpload.prototype = {
    init: function () {
        this.me.append(this.preview);
        this.me.append(this.fileupload);
        this.cssInit();
        this.eventClickInit();
    }, cssInit: function () {
        this.me.css({
            'width': this.opts.boxWidth,
            'height': this.opts.boxHeight,
            'border': '1px solid #cccccc',
            'padding': '10px',
            'cursor': 'pointer'
        })
        this.preview.css({'height': '100%', 'overflow': 'hidden'})
    }, onDragover: function (e) {
        e.stopPropagation();
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    }, onDrop: function (e) {
        var self = this;
        e.stopPropagation();
        e.preventDefault();

        this.checkEdit(e.dataTransfer.files,self);
    },
    checkEdit: function(fileList,self) {
        if (fileList.length == 0) {
            return false;
        }
        var uploadType = CONFIG_UPLOAD[self.opts.uploadType] ? CONFIG_UPLOAD[self.opts.uploadType].type : self.opts[self.opts.key].type;
        if (fileList[0].type.indexOf(uploadType) === -1) {
            var errotMsg = CONFIG_UPLOAD[self.opts.uploadType] ? CONFIG_UPLOAD[self.opts.uploadType].errotMsg : self.opts[self.opts.key].errotMsg;
            alert(errotMsg);
            return false;
        }
        var imgBool = self.opts.uploadType == 'image';
        var img = null;
        if (imgBool) {
            img = window.URL.createObjectURL(fileList[0]);
        }
        var filename = fileList[0].name;
        var filesize = Math.floor((fileList[0].size) / 1024);
        var maxSize = CONFIG_UPLOAD[self.opts.uploadType] ? CONFIG_UPLOAD[self.opts.uploadType].size : self.opts[self.opts.key].size;
        if (filesize > maxSize ) {
            alert("上传大小不能超过" + ( Math.floor(maxSize/1024 * 1000,3) / 1000 ) + "MB");
            return false;
        }
        self.me.find(self.opts[self.opts.key].hideClass).remove();
        if (img) {
            self.me.find(".dragUpload-view img").attr("src", img);
            self.me.find(".dragUpload-view img").attr("title", filename);
        }
        if (this.callback) {
            this.callback(fileList, this.previewUploadDom,this.preview);
        }
    },
    eventClickInit: function () {
        var self = this;
        this.me.unbind().click(function () {
            self.createImageUploadDialog();
        })
        var dp = this.me[0];
        dp.addEventListener('dragover', function (e) {
            self.onDragover(e);
        });
        dp.addEventListener("drop", function (e) {
            self.onDrop(e);
        });
    }, onChangeUploadFile: function () {
        var fileInput = this.fileInput;
        var files = fileInput.files;
        var self = this;
        this.checkEdit(files,self);
    }, createImageUploadDialog: function () {
        var fileInput = this.fileInput;
        if (!fileInput) {
            fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.name = 'ime-images';
            fileInput.multiple = true;
            fileInput.onchange = this.onChangeUploadFile.bind(this);
            this.fileInput = fileInput;
        }
        fileInput.click();
    }
}