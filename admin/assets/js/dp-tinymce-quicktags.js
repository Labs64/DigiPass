(function ($) {
    "use strict";
    $(function () {
        tinymce.create('tinymce.plugins.Digipass', {
            init: function (ed, url) {
                ed.addButton('digipass', {
                    title: 'DigiPass',
                    cmd: 'digipass',
                    icon: false,
                    text: 'DigiPass'
                });

                ed.addCommand('digipass', function () {
                    var parent, html, title,
                        classname = 'digipass-tag',
                        dom = ed.dom,
                        node = ed.selection.getNode();


                    title = 'DigiPAss...';
                    html = '<img src="' + tinymce.Env.transparentSrc + '" title="' + title + '" class="' + classname + '" data-digipass="digipass"/>';

                    parent = dom.getParent(node, function (found) {
                        return ( found.parentNode && found.parentNode.nodeName === 'BODY' ) ? true : false;
                    }, ed.getBody());

                    dom.insertAfter(dom.create('p', null, html), parent);
                    return false;
                });

                // Replace DigiPass tags with images
                ed.on('BeforeSetContent', function (event) {
                    var title;

                    if (event.content) {
                        if (event.content.indexOf('<!--digipass-->') !== -1) {
                            title = 'Digipass';

                            event.content = event.content.replace(/<!--digipass-->/g, function (match) {
                                return '<img src="' + tinymce.Env.transparentSrc + '"' +
                                    'class="digipass-tag" title="' + title + '" data-digipass="digipass"/>';
                            });
                        }
                    }
                });

                // Replace images with tags
                ed.on('PostProcess', function (e) {
                    if (e.get) {
                        e.content = e.content.replace(/<img[^>]+>/g, function (image) {
                            var match, moretext = '';
                            if (image.indexOf('data-digipass="digipass"') !== -1) {
                                image = '<!--digipass-->';
                            }
                            return image;
                        });
                    }
                });
            }
        });
        // Register plugin
        tinymce.PluginManager.add('digipass', tinymce.plugins.Digipass);

    });
}(jQuery));
