/**
 * Core 3 CMS - Bundled WYSIWYG Editor (Zero Dependencies)
 */
(function(){
'use strict';
function Ed(el,field,ph){
    this.el=el;this.field=field;
    var w=document.createElement('div');w.className='c3-editor-wrap';
    this.tb=document.createElement('div');this.tb.className='c3-toolbar';
    this.ed=document.createElement('div');this.ed.className='c3-editable';
    this.ed.contentEditable=true;this.ed.setAttribute('data-placeholder',ph);
    this.ed.innerHTML=field.value||'';
    this.src=document.createElement('textarea');this.src.className='c3-source';this.src.spellcheck=false;
    this.mode='visual';this.build();
    w.appendChild(this.tb);w.appendChild(this.ed);w.appendChild(this.src);
    el.innerHTML='';el.appendChild(w);
    var s=this;
    this.ed.addEventListener('input',function(){s.sync()});
    this.ed.addEventListener('keyup',function(){s.active()});
    this.ed.addEventListener('mouseup',function(){s.active()});
    this.ed.addEventListener('paste',function(e){s.paste(e)});
    this.ed.addEventListener('keydown',function(e){if(e.key==='Tab'){e.preventDefault();s.exec(e.shiftKey?'outdent':'indent')}});
    var form=field.closest('form');
    if(form)form.addEventListener('submit',function(){s.sync()});
}
Ed.prototype.build=function(){
    var s=this;
    // Format select
    var fmt=document.createElement('select');fmt.title='Format';
    [['p','Paragraph'],['h1','Heading 1'],['h2','Heading 2'],['h3','Heading 3'],['pre','Code'],['blockquote','Quote']].forEach(function(f){
        var o=document.createElement('option');o.value=f[0];o.textContent=f[1];fmt.appendChild(o);
    });
    fmt.addEventListener('change',function(){s.exec('formatBlock','<'+this.value+'>');this.value='p';s.ed.focus()});
    this.tb.appendChild(fmt);this.fmtSel=fmt;
    this.sep();
    // Inline
    [['bold','<b>B</b>'],['italic','<i>I</i>'],['underline','<u>U</u>'],['strikeThrough','<s>S</s>']].forEach(function(b){s.btn(b[0],b[1])});
    this.sep();
    s.btn('insertUnorderedList','• List');s.btn('insertOrderedList','1. List');
    this.sep();
    s.btn('justifyLeft','⇤');s.btn('justifyCenter','⇔');s.btn('justifyRight','⇥');
    this.sep();
    // Link
    var lb=document.createElement('button');lb.type='button';lb.innerHTML='🔗';lb.title='Link';
    lb.addEventListener('click',function(){var u=prompt('URL:','https://');if(u){var t=window.getSelection().toString();if(t)s.exec('createLink',u);else s.exec('insertHTML','<a href="'+u+'" target="_blank">'+u+'</a>')}});
    this.tb.appendChild(lb);
    // Image
    var ib=document.createElement('button');ib.type='button';ib.innerHTML='🖼';ib.title='Image URL';
    ib.addEventListener('click',function(){var u=prompt('Image URL:','https://');if(u)s.exec('insertHTML','<img src="'+u+'">')});
    this.tb.appendChild(ib);
    // HR
    var hr=document.createElement('button');hr.type='button';hr.innerHTML='—';hr.title='Horizontal line';
    hr.addEventListener('click',function(){s.exec('insertHorizontalRule')});
    this.tb.appendChild(hr);
    this.sep();
    // Clean
    var cl=document.createElement('button');cl.type='button';cl.innerHTML='✕';cl.title='Remove formatting';cl.style.fontSize='11px';
    cl.addEventListener('click',function(){s.exec('removeFormat')});
    this.tb.appendChild(cl);
    this.sep();
    // Source toggle
    var st=document.createElement('button');st.type='button';st.innerHTML='&lt;/&gt;';st.title='HTML source';
    st.style.fontFamily='monospace';st.style.fontSize='11px';this.srcBtn=st;
    st.addEventListener('click',function(){s.toggleSrc()});
    this.tb.appendChild(st);
};
Ed.prototype.btn=function(cmd,label){
    var s=this,b=document.createElement('button');b.type='button';b.innerHTML=label;b.title=cmd;b.dataset.cmd=cmd;
    b.addEventListener('click',function(){s.exec(cmd);s.ed.focus()});this.tb.appendChild(b);
};
Ed.prototype.sep=function(){var s=document.createElement('span');s.className='sep';this.tb.appendChild(s)};
Ed.prototype.exec=function(c,v){document.execCommand(c,false,v||null);this.sync();this.active()};
Ed.prototype.sync=function(){this.field.value=this.mode==='visual'?this.ed.innerHTML:this.src.value};
Ed.prototype.active=function(){
    var btns=this.tb.querySelectorAll('button[data-cmd]');
    btns.forEach(function(b){try{b.classList.toggle('active',document.queryCommandState(b.dataset.cmd))}catch(e){}});
    try{var bl=document.queryCommandValue('formatBlock').toLowerCase().replace(/[<>]/g,'');if(this.fmtSel)this.fmtSel.value=bl||'p'}catch(e){}
};
Ed.prototype.toggleSrc=function(){
    if(this.mode==='visual'){
        this.src.value=this.ed.innerHTML.replace(/></g,'>\n<');
        this.ed.style.display='none';this.src.style.display='block';this.mode='source';this.srcBtn.classList.add('active');
        this.tb.querySelectorAll('button:not(:last-child),select').forEach(function(e){e.disabled=true;e.style.opacity='.3'});
    }else{
        this.ed.innerHTML=this.src.value;this.src.style.display='none';this.ed.style.display='block';this.mode='visual';this.srcBtn.classList.remove('active');
        this.tb.querySelectorAll('button,select').forEach(function(e){e.disabled=false;e.style.opacity='1'});
    }
    this.sync();
};
Ed.prototype.paste=function(e){
    var cd=e.clipboardData;if(!cd)return;var h=cd.getData('text/html');
    if(h){e.preventDefault();h=h.replace(/<!--[\s\S]*?-->/g,'').replace(/<\/?(?:meta|style|script|link)[^>]*>/gi,'').replace(/\s*(?:class|style|id)\s*=\s*"[^"]*"/gi,'').replace(/<span\s*>([\s\S]*?)<\/span>/gi,'$1');this.exec('insertHTML',h)}
};
// Auto-init
document.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('[data-c3-editor]').forEach(function(el){
        var fid=el.getAttribute('data-c3-editor'),f=document.getElementById(fid);
        if(f)new Ed(el,f,el.getAttribute('data-placeholder')||'Start writing...');
    });
});
})();
