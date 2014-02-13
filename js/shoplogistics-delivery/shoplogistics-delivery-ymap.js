var yMap;
var courierList;

function check_sub_variant(button) {
    var $btn = jQuery(button);
    $btn.removeClass('btn-primary').addClass('btn-success').html('Выбрано');
}

function getGeoObject( place, callback ) {
    if ( !(typeof callback == 'function') )
        return;

    place.address = place.address.replace( /([Мм]\. ?«?[^,]+»?, )/, '' );
    var gc = ymaps.geocode( place.city + ' ' + place.address, {json:true});
    gc.then(
        function (res) {
            if( res.GeoObjectCollection.featureMember.length ) {
                var GeoObject = res.GeoObjectCollection.featureMember[0].GeoObject;
                if ( typeof callback == 'function' )
                    callback( GeoObject, place );
            }
        },
        function (err) {}
    );
}

function addPlacemark( place ) {
    getGeoObject( place, function( GeoObject, place ) {
        var pos = GeoObject.Point.pos.split(' ').reverse();

		var preset = 'twirl#lightblueStretchyIcon';
		if( place.id == jQuery('input[name=shipping_method_sub_variant]:first').val() )
			preset = 'twirl#greenStretchyIcon';
			
        var pm = new ymaps.Placemark( pos, {}, {} );
        yMap.geoObjects.add(pm);
		var options = {
			preset: preset
		};
		
        var properties = {
            hintContent: place.type + " - " + place.address, 
            balloonContent: place.address,
            balloonContentHeader: place.type,
			balloonContentBody: [
				'<address><strong>', place.address, '</strong>.<br/><br/>',
				'Стоимость: <strong>', parseFloat(place.price), '</strong> рублей.<br/>',
				( place.worktime ? 'Часы работы: '+ place.worktime + '.<br/>' : '' ),
				'Срок доставки: ', place.deliverytime, '.</address>',
				'<button class="btn btn-primary pull-right" data-value="' + place.id + '" id="set_shipping_method_subvariant', place.id, '">Выбрать</button>'
			].join(''),
			place: place,
            iconContent: '<strong>' + parseFloat(place.price) + '</strong> р.'
        };
		pm.properties.set(properties);
		pm.options.set(options);

        pm.balloon.events.add('open', function(){
			var btn = document.getElementById('set_shipping_method_subvariant' + place.id);
			btn['data-marker'] = this;
			
			if( jQuery('input[name=shipping_method_sub_variant]:first').val() == jQuery(btn).data().value )
				jQuery(btn).removeClass('btn-primary').addClass('btn-success').text('Уже выбран').attr('disabled','disabled');
			
			jQuery(btn).on('click', function(){
				var pm = this['data-marker'];
				var balloon = pm.balloon;
				var place = pm.properties.get('place');
				
                if( courierList ) {
                    courierList.each(function(item){
                        item.deselect();
                    });
                    courierList.setTitle( '<strong>Есть</strong> курьеская доставка до дома!' );
                }
				yMap.geoObjects.each(function (geoObject) {
                    var place = geoObject.properties.get('place');
					geoObject.options.set( {preset: 'twirl#lightblueStretchyIcon'} );
					geoObject.properties.set( {hintContent: place.type + " - " + place.address} );
				});

				pm.options.set( {preset: 'twirl#greenStretchyIcon'} );
				pm.properties.set( {hintContent: '<strong>Выбран</strong> - ' + place.type + " - " + place.address} );
                balloon.close();

                updateChosenVariant( place );
                updateMapTopHint( place );
            });
        }, pm );

        jQuery(document).trigger( "ymap:coords:updated" );
    });
}

function updateChosenVariant(place) {
    var current = jQuery('input[name=shipping_method_sub_variant]:first').val();
    if( current != place.id ) {
        jQuery('input[name=shipping_method_sub_variant]:first').val( place.id ).trigger( "change" );
        //jQuery('button#confirm').show();
        jQuery("#showMap").modal('hide');
        return true;
    }
    return false;
}

function updateMapTopHint(place) {
    var $block = jQuery('.hint.chosen');

    $block.find('.text').html('Выбрано:');
    $block.find('.type').html( '<strong>' + place.type + '</strong>' + ( /урьер/.test(place.type) ? '' : ' по адресу: ') );
    $block.find('.street_address').html(place.address);
    $block.find('.price').html( 'Стоимость доставки: <strong>' + place.price + ' руб</strong>.');
    $block.find('.deliverytime').html(place.deliverytime);
    return true;
}

//ymaps.ready();
function yMapCreate (){
    var yPlacemark;

    jQuery(document).on('ymap:coords:updated', function(){
        yMap.setBounds( yMap.geoObjects.getBounds() );
        var zoom = yMap.getZoom();
        if( zoom > 15 ) yMap.setZoom(15);
    });

    //yMap = new ymaps.Map ("map", { center: [55.76, 37.64], zoom: 11, controls: ['searchControl', 'trafficControl', 'fullscreenControl'] });
    yMap = new ymaps.Map ("map", { center: [55.76, 37.64], zoom: 11, yandexMapAutoSwitch: true });
    yMap.controls.add('zoomControl');

    var courierListItems = new Array();

    jQuery( 'label[for="' + jQuery('.shipping_methods.collection .shipping_method.shoplogistics_delivery').attr('id') + '"] .subvariant .option').each(function(){
        var $this = jQuery(this);
        var place = {};

        place.city = jQuery('select.shoplogistics_delivery option[selected]').text();
        place.id    = $this.find('input.shipping_method_sub_variant').val();
        place.price = parseFloat( ($this.find('.digit').text()).replace(/[^0-9\.,]+/,'') );
        place.deliverytime = $this.find('.delivery').text().replace( /[()]/g,'');
        place.address = $this.find('.street_address').text();
        place.worktime = $this.find('.street_address').data('worktime');
        place.type     = $this.find('.type').html();

        if( $this.find('span.type').hasClass('courier') ) {
            var item = new ymaps.control.ListBoxItem( {data: {content: '<strong>' + place.price + '</strong> руб. - ' + place.deliverytime }} );
            item.place = place;

            courierListItems.push( item );
            item.events.add('click', function () {
                this.getParent().collapse();
                this.getParent().each(function(item){ item.deselect(); });

                yMap.geoObjects.each(function (geoObject) {
                    var place = geoObject.properties.get('place');
                    geoObject.options.set( {preset: 'twirl#lightblueStretchyIcon'} );
                    geoObject.properties.set( {hintContent: place.type + " - " + place.address} );
                });
                this.getParent().setTitle( this.place.type + ' - <strong>' + this.place.price + '</strong> руб. (' + this.place.deliverytime + ')' );
                this.select(); 

                updateChosenVariant( this.place );
                updateMapTopHint( this.place );
            }, item );
        } else {
            addPlacemark(place);
        }
    });

    if( courierList )
        courierList = null;
    if( courierListItems.length > 0 ) {
        courierList = new ymaps.control.ListBox({ data: { title: '<strong>Есть</strong> курьеская доставка до дома!' }, items: courierListItems });
        yMap.controls.add( courierList );
    }
    courierListItems = null;
}

function yMapDestroy() {
    if(yMap) {
        yMap.destroy();
        yMap = null;
    }
}
