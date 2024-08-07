var initPhotoSwipeFromDOM = function(el) {

    // parse slide data (url, title, size ...) from DOM elements 
    // (children of el)
    var parseThumbnailElements = function(el) {
        var thumbElements = el.childNodes,
            numNodes = thumbElements.length,
            items = [],
            figureEl,
            linkEl,
            size,
            item;

        for(var i = 0; i < numNodes; i++) {

            figureEl = thumbElements[i]; // <figure> element

            // include only element nodes 
            if(figureEl.nodeType !== 1) {
                continue;
            }

            linkEl = figureEl.children[0]; // <a> element

            size = linkEl.getAttribute('data-size').split('x');

            // create slide object
            item = {
                src: linkEl.getAttribute('href'),
                w: parseInt(size[0], 10),
                h: parseInt(size[1], 10)
            };



            if(figureEl.children.length > 1) {
                // <figcaption> content
                item.title = figureEl.children[1].innerHTML; 
            }

            if(linkEl.children.length > 0) {
                // <img> thumbnail element, retrieving thumbnail url
                item.msrc = linkEl.children[0].getAttribute('src');
            } 

            item.el = figureEl; // save link to element for getThumbBoundsFn
            items.push(item);
        }

        return items;
    };

    // find nearest parent element
    var closest = function closest(el, fn) {
        return el && ( fn(el) ? el : closest(el.parentNode, fn) );
    };

    // triggers when user clicks on thumbnail
    var onThumbnailsClick = function(e) {
        e = e || window.event;
        e.preventDefault ? e.preventDefault() : e.returnValue = false;

        var eTarget = e.target || e.srcElement;

        // find root element of slide
        var clickedListItem = closest(eTarget, function(el) {
            return (el.tagName && el.tagName.toUpperCase() === 'FIGURE');
        });

        if(!clickedListItem) {
            return;
        }

        // find index of clicked item by looping through all child nodes
        // alternatively, you may define index via data- attribute
        var clickedGallery = clickedListItem.parentNode,
            childNodes = clickedListItem.parentNode.childNodes,
            numChildNodes = childNodes.length,
            nodeIndex = 0,
            index;

        for (var i = 0; i < numChildNodes; i++) {
            if(childNodes[i].nodeType !== 1) { 
                continue; 
            }

            if(childNodes[i] === clickedListItem) {
                index = nodeIndex;
                break;
            }
            nodeIndex++;
        }



        if(index >= 0) {
            // open PhotoSwipe if valid index found
            openPhotoSwipe( index, clickedGallery );
        }
        return false;
    };

    // parse picture index and gallery index from URL (#&pid=1&gid=2)
    var photoswipeParseHash = function() {
        var hash = window.location.hash.substring(1),
        params = {};

        if(hash.length < 5) {
            return params;
        }

        var vars = hash.split('&');
        for (var i = 0; i < vars.length; i++) {
            if(!vars[i]) {
                continue;
            }
            var pair = vars[i].split('=');  
            if(pair.length < 2) {
                continue;
            }           
            params[pair[0]] = pair[1];
        }

        if(params.gid) {
            params.gid = parseInt(params.gid, 10);
        }

        return params;
    };

    var openPhotoSwipe = function(index, galleryElement, disableAnimation, fromURL) {
        var pswpElement = document.querySelectorAll('.pswp')[0],
            gallery,
            options,
            items;

        items = parseThumbnailElements(galleryElement);

        // define options (if needed)
        options = {
            // define gallery index (for URL)
            galleryUID: galleryElement.getAttribute('data-pswp-uid'),

            getThumbBoundsFn: function(index) {
                // See Options -> getThumbBoundsFn section of documentation for more info
                var thumbnail = items[index].el.getElementsByTagName('img')[0], // find thumbnail
                    pageYScroll = window.pageYOffset || document.documentElement.scrollTop,
                    rect = thumbnail.getBoundingClientRect(); 

                return {x:rect.left, y:rect.top + pageYScroll, w:rect.width};
            }

        };

        // PhotoSwipe opened from URL
        if(fromURL) {
            if(options.galleryPIDs) {
                // parse real index when custom PIDs are used 
                // http://photoswipe.com/documentation/faq.html#custom-pid-in-url
                for(var j = 0; j < items.length; j++) {
                    if(items[j].pid == index) {
                        options.index = j;
                        break;
                    }
                }
            } else {
                // in URL indexes start from 1
                options.index = parseInt(index, 10) - 1;
            }
        } else {
            options.index = parseInt(index, 10);
        }

        // exit if index not found
        if( isNaN(options.index) ) {
            return;
        }

        if(disableAnimation) {
            options.showAnimationDuration = 0;
        }

        // Pass data to PhotoSwipe and initialize it
        gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options);
        gallery.init();

        gallery.listen('afterChange', function() {
          var $slick = $(galleryElement).closest('[slick]');
          if ($slick.length) {
            var index = gallery.getCurrentIndex() - 1;
            $slick.slick('slickGoTo', index, true);
          }
        });
    };

    // loop through all gallery elements and bind events
    var galleryElements = typeof el === 'string' ? document.querySelectorAll( el ) : [el];

    for(var i = 0; i < galleryElements.length; i++) {
        galleryElements[i].setAttribute('data-pswp-uid', i+1);
        galleryElements[i].onclick = onThumbnailsClick;
    }

    // Parse URL and open gallery if it contains #&pid=3&gid=1
    var hashData = photoswipeParseHash();
    if(hashData.pid && hashData.gid) {
        openPhotoSwipe( hashData.pid ,  galleryElements[ hashData.gid - 1 ], true, true );
    }
};

$(function() {
  $('[photoswipe]').each(function(i, el) {
    // execute above function
    initPhotoSwipeFromDOM(el);
  });
});

$(function() {
  $('[jqueryui]').each(function(i, item) {
    var plugin = item.getAttribute('jqueryui');
    var optionsJSON = item.getAttribute('options')
    var options = optionsJSON ? JSON.parse(optionsJSON) : {};
    $(item)[plugin]();
  });

  $('[jqueryui="tabs"]').on('tabsactivate', function(event, ui) { 
    history.pushState(null, null, '#'+ui.newPanel.attr('id'));
    
    // Trigger layout
    $(ui.newPanel).find('[masonry]').each(function(i, el) { 
      $(el).masonry('layout');
    })
  });

  $('[jqueryui="accordion"]').accordion({
    collapsible: true,
    active: false
  });

  var slickDefaults = {
    // centerMode: true,
    speed: 100,
    infinite: true,
    variableWidth: true
  };
  $('[slick]').each(function(index, el) {
    var options = el.getAttribute('jqueryuioptions');
    try {
      options = JSON.parse(options);
      for (var prop in slickDefaults) {
        if (typeof options[prop] === 'undefined') {
          options[prop] = slickDefaults[prop];
        }
      }
    }
    catch(err) {
      options = slickDefaults;
    }
    options = options || slickDefaults
    $(el).slick(options).addClass('slick');
  });

  $(document.body).on('click', '[trackclick]', function() {
    var trackClick = this.getAttribute('trackclick')
      .replace('$hostname', this.hostname)
      .replace('$innerText', this.innerText)
    var parts = trackClick.split(',');

    // console.log('send', 'event', parts[0], parts[1], parts[2], parts[3]);
    ga('send', 'event', parts[0], parts[1], parts[2], parts[3]);
  });

  $(window).on('hashchange', function(event) {
    if (window.location.hash === '') {
      // First tab
      $('#main-tabs ul li a').first().click();
    }
    else {
      // Other tabs
      $('[jqueryui="tabs"]').find('[href='+JSON.stringify(window.location.hash)+']').click();
    }
  });
});

$(window).on('load', function() {
  $('[masonry]').each(function(i, el) { 
    $(el).masonry({
      // options
      itemSelector: 'figure'
    }).addClass('masonry');
  });
});
