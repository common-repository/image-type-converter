( function($){
    "use strict";
    var prefix                    = 'gpls-wicor-image-converter';
    var prefixUnderscored         = 'gpls_wicor_image_converter';
    var localize_vars             = window[ prefixUnderscored + '_localize_vars' ];

    $(document).on( 'ready', (e) => {
        new SelectImagesModule();
    });

    class SelectImagesModule {

        constructor() {
            this.cpts                 = [];
            this.sizes                = [];
            this.sizeAction           = '';
            this.selectedImagesDirect = [];
            this.selectedImagesCPTs   = [];
            this.foundImages          = {};
            this.foundImagesPages     = 1;
            this.selectedImages       = [];

            this.events();
        }

        insertSelectedDirectImages( attachments ) {
            // console.log( 'attachments: ', attachments );
            var sortableWrapper = $('.' + localize_vars['classes_prefix'] + '-selected-images-direct');
            attachments.forEach( (attachment) => {
                var imgItem  = sortableWrapper.find('.img-item-clone').clone().removeClass('img-item-clone d-none').addClass('img-item');
                var editLink = attachment.editLink;
                editLink     = new URL( editLink );
                editLink.searchParams.set( localize_vars['classes_prefix'] + '-force-img-refresh', ( Math.random() + 1 ).toString(36).substring(7) );
                imgItem.find('.frame-remove').attr('data-id', attachment['id'] );
                imgItem.find('a').attr('href', editLink.href );
                imgItem.find('img').attr('src', ( attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url ) );
                sortableWrapper.append( imgItem );
            });
        }

        events() {

            $('.main-loader').hide();
            $('.main-loader .fetching-data').addClass('d-none');
            $('[data-toggle="tooltip"]').tooltip();


            $(document).on( 'click', '.close-modal-btn', (e) => {
                e.preventDefault();
                var $this = $(e.target);
                var modal = $this.closest('.bulk-apply-modal');
                modal.modal('hide');
            });

            /// ====== Step 1 ========= ///

            // Select direct Images.
            $( '.apply-watermarks-template-selection-direct' ).on( 'click', (e) => {
                e.preventDefault();
                var mediaUploader;
                var $this = $(e.target);
                var context = $this.data('context');
                var options = {
                    title: localize_vars.labels.select_images,
                    library: {
                        orderby: 'date',
                        query: true,
                        post_mime_type: [ 'image/png', 'image/jpg', 'image/jpeg', 'image/webp', 'image/avif', 'image/gif' ],
                        [ prefix + '-context-modal' ]: 'create-watermark-template'
                    },
                    button: {
                        text: localize_vars.labels.select_images
                    },
                    multiple: true
                }

                mediaUploader = wp.media( options );
                mediaUploader.open();

                mediaUploader.on( 'select', (e) => {
                    var attachments = mediaUploader.state().get('selection').toJSON();
                    attachments.forEach( (attachment) => {
                        this.selectedImagesDirect.push( attachment['id'] );
                    });
                    this.selectedImages = this.selectedImagesDirect;
                    this.insertSelectedDirectImages( attachments );
                    this.toggleSteps();
                });
            });

            // Remove Direct Selected Image.
            $(document).on( 'click', '.' + localize_vars['classes_prefix'] + '-selected-images-direct .frame-remove', (e) => {
                var $this = $(e.target);
                if ( ! $this.hasClass('.frame-remove') ) {
                    $this = $this.parents('.frame-remove');
                }
                var attachmentID = $this.data('id');
                var imgIndex     = this.selectedImagesDirect.indexOf( parseInt( attachmentID ) );
                if ( imgIndex > -1 ) {
                    this.selectedImagesDirect.splice( imgIndex, 1 );
                    $this.parents('.img-item').remove();
                    this.selectedImages = this.selectedImagesDirect;
                }
            });

            // Toggle Select Images by options content accordion.
            $('.select-images-by-option').on( 'change', (e) => {
                var $this      = $(e.target);
                var selectType = $this.val();
                $('#select-images-by-' + selectType ).collapse('show').siblings('.select-images-by-option-content').collapse('hide');

                if ( 'cpt' === selectType ) {
                    this.selectedImages = this.selectedImagesCPTs;
                } else if ( 'direct' === selectType ) {
                    this.selectedImages = this.selectedImagesDirect;
                }

                if ( 'full' === selectType ) {
                    $('.step-2').collapse('show');
                } else {
                }

                this.toggleSteps();

            });


            // On Select CPT.
            $('#select-images-by-cpt').on( 'change', '.cpt-name-checkbox', (e) => {
                let cptCheckbox      = $(e.targe);
                let checkedCPTsCount = $('.cpt-name-checkbox:checked').length

                if ( ! checkedCPTsCount ) {
                    $('.search-for-images').collapse('hide');
                } else {
                    $('.search-for-images').collapse('show');
                }
                this.toggleSteps();
            });


            /// ==== Step 2 ==== ///

            // Toggle Select All functionality
            $('.bulk-select-sizes').on( 'click', (e) => {
                var $this            = $(e.target);
                var checkSelectStep = $this.parents('.step-2');
                if ( $this.prop('checked') == true ) {
                    checkSelectStep.find('.size-item').prop('checked', true );
                } else {
                    checkSelectStep.find('.size-item').prop('checked', false );
                }
                $('.step-3').collapse('show');
            });

            $('.size-item').on('change', (e) => {
                $('.step-4').collapse('show');
            });

            // === Search for images in CPTs === //.

            // Search For images in selected Posts.
            $('.search-for-images-in-posts').on( 'click', async (e) => {
                e.preventDefault();
                let response          = await this.findImagesByPosts( 1, true );
                let attachments_count = response.data.result.attachments_count;
                $('.found-images-count').show();
                $('.found-images-count').find('.images-count').text( attachments_count );
                if ( attachments_count ) {
                    $('.select-specific-images').show();
                } else {
                    $('.select-specific-images').hide();
                }
            });

            $('.select-specific-images').on( 'click', (e) => {
                e.preventDefault();
                $('.selected-images-watermarks-template-modal').modal('show');
                if ( this.foundImages[1] ) {
                    var paginationWrapper = $('.selected-images-watermarks-template-modal .actions' );
                    this.updateListFoundImages( this.foundImages[1], paginationWrapper, 1, this.foundImagesPages, true );
                } else {
                    this.findImagesByPosts();
                }
            });

            $('.selected-images-watermarks-template-modal .next-page').on( 'click', (e) => {
                var $this = $(e.target);
                if ( ! $this.hasClass('button') ) {
                    $this = $this.parent();
                }
                var paginationWrapper = $('.selected-images-watermarks-template-modal .actions' );
                var allPages          = parseInt( paginationWrapper.find('.total-pages').data('pages') );
                var currentPage       = parseInt( paginationWrapper.find('.current-page').text() );
                var nextPage          = currentPage + 1;

                // Fill the Table.
                if ( this.foundImages[ nextPage ] ) {
                    this.updateListFoundImages( this.foundImages[ nextPage ], paginationWrapper, nextPage, allPages, true );
                } else {
                    this.findImagesByPosts( nextPage );
                }
            });

            $('.selected-images-watermarks-template-modal .prev-page').on( 'click', (e) => {
                var $this = $(e.target);
                if ( ! $this.hasClass('button') ) {
                    $this = $this.parent();
                }
                var paginationWrapper = $('.selected-images-watermarks-template-modal .all-found-images-pagination' );
                var allPages          = parseInt( paginationWrapper.find('.total-pages').data('pages') );
                var currentPage       = parseInt( paginationWrapper.find('.current-page').text() );
                var prevPage          = currentPage - 1;

                // Update the current page val.
                paginationWrapper.find('.current-page').val( prevPage );
                if ( this.foundImages[ prevPage ] ) {
                    this.updateListFoundImages( this.foundImages[ prevPage ], paginationWrapper, prevPage, allPages, true );
                } else {
                    this.findImagesByPosts( prevPage );
                }
            });

            $('.selected-images-watermarks-template-modal .first-page').on( 'click', (e) => {
                var $this = $(e.target);
                if ( ! $this.hasClass('button') ) {
                    $this = $this.parent();
                }
                var paginationWrapper = $('.selected-images-watermarks-template-modal .all-found-images-pagination' );
                var allPages          = parseInt( paginationWrapper.find('.total-pages').data('pages') );
                var firstPage         = 1;

                // Update the current page val.
                paginationWrapper.find('.current-page').val( firstPage );
                if ( this.foundImages[ firstPage ] ) {
                    this.updateListFoundImages( this.foundImages[ firstPage ], paginationWrapper, 1, allPages, true );
                } else {
                    this.findImagesByPosts( firstPage );
                }
            });

            $('.selected-images-watermarks-template-modal .last-page').on( 'click', (e) => {
                var $this = $(e.target);
                if ( ! $this.hasClass('button') ) {
                    $this = $this.parent();
                }
                var paginationWrapper = $('.selected-images-watermarks-template-modal .all-found-images-pagination' );
                var allPages          = parseInt( paginationWrapper.find('.total-pages').data('pages') );
                var lastPage          = parseInt( $this.data('paged') )

                // Update the current page val.
                paginationWrapper.find('.current-page').val( lastPage );
                if ( this.foundImages[ lastPage ] ) {
                    this.updateListFoundImages( this.foundImages[ lastPage ], paginationWrapper, lastPage, allPages, true );
                } else {
                    this.findImagesByPosts( lastPage );
                }
            });

            $(document).on( 'change', '.cpt-name-checkbox', (e) => {
                this.foundImages = {};
            });

            $('.apply-subsize-actions-submit-btn').on( 'click', (e) => {
                e.preventDefault();
                this.applyConvert();
            });

            $(document).on('change', '.cb-select-all-1', (e) => {
                var $this     = $(e.target);
                var table     = $this.parents('.wp-list-table');
                var isChecked = $this.is(':checked');
                $.each( table.find('.cb-select-all'), (index, element) => {
                    $(element).prop('checked', isChecked ).trigger('change');
                });
            });


            $(document).on('change', '#modal-found-images-watermarks-template .cb-select-all', (e) => {
                var $this = $(e.target);
                this.updateSelectedImages( $this );
            });
        }

        applyConvert( step = 0 ) {
            this.toggleLoader( '', 'show', true );
            var totalSteps = Math.ceil( this.selectedImages.length / parseInt( localize_vars.offsetLength ) );
            if ( step == 0 ) {
                $('.main-loader .loader-progress').val( 0 );
                $('.main-loader .loader-progress-num').removeClass('d-none').text('0%');
            }

            let selectType = $('.select-images-by-option:checked').val();
            let targetType = $('.size-item:checked').val();
            let keepExt    = $('.keep-ext').is(':checked');

            if ( selectType === 'full' ) {
                let totalImgs = parseInt( $('#select-images-by-full').data('count') );
                totalSteps    = Math.ceil( totalImgs / parseInt( localize_vars.offsetLength ) );
            }

            var data = {
                action: localize_vars.bulkTypeConvertAction,
                nonce: localize_vars.nonce,
                images: this.selectedImages,
                step: step,
                totalSteps: totalSteps,
                selectType: selectType,
                type: targetType,
                keepExt: keepExt
            }

            $.ajax({
                method: 'POST',
                url: localize_vars.ajaxUrl,
                data: data,
                success: (resp) => {
                    if ( resp?.data && resp?.data?.message && resp.data.message.length ) {
                        this.showToast( resp?.data?.message, 'bg-' + resp.data.status );
                    }
                    if ( resp['success'] === false ) {
                        this.toggleLoader( '', 'hide' );
                    }
                    if ( resp['data']['status'] && resp['success'] ) {
                        if ( 'end' === resp['data']['step'] ) {
                            $('.main-loader .loader-progress').val(100);
                            $('.main-loader .loader-progress-num').text('100%');
                            setTimeout(() => {
                                this.toggleLoader( '', 'hide' );
                            }, 2000 );
                        } else {
                            var progress = Math.round( parseFloat( parseInt( resp['data']['step'] ) / totalSteps ) * 100 );
                            $('.main-loader .loader-progress').val( progress );
                            $('.main-loader .loader-progress-num').text( progress + '%' );
                            this.applyConvert( resp['data']['step'] );
                        }
                    }
                },
                error: (err) => {
                    console.log( 'error:', err );
                    if ( err?.data?.message ) {
                        this.showToast( err.data.message, 'bg-danger' );
                    }
                }
            });
        }

        updateListFoundImages( images, actionsRow, currentPage, totalPages, checkSelected = false ) {
            var wrapper     = $( '.selected-images-watermarks-template-modal' );
            var wrapperBody = wrapper.find('.wp-list-table').find('tbody');

            wrapperBody.html( images );

            if ( checkSelected ) {
                $.each( wrapper.find('.cb-select-all'), (index, element) => {
                    var $this   = $(element);
                    var imageID = parseInt( $this.data('id') );
                    if ( this.selectedImages.includes( imageID ) ) {
                        $this.prop('checked', true);
                    } else {
                        $this.prop('checked', false);
                    }
                });
            }
            if ( images.length ) {
                $('.step-3').collapse('show');
            } else {
                $('.step-3').collapse('hide');
            }
            this.setupPagination( actionsRow, currentPage, totalPages );
        }

        setupPagination( paginationWrapperElement, currentPage, pagesCount ) {
            // First Page.
            paginationWrapperElement.find('.first-page').data( 'paged', 1 );
            // Current Page.
            paginationWrapperElement.find('.current-page').text( currentPage );
            // Total Pages.
            paginationWrapperElement.find('.total-pages').data('pages', pagesCount ).text( pagesCount );
            // Next Page.
            paginationWrapperElement.find('.next-page').data( 'paged', ( currentPage + 1 ) );
            // Last Page.
            paginationWrapperElement.find('.last-page').data( 'paged', pagesCount );

            paginationWrapperElement.find('.next-page').removeClass( 'disabled' );
            paginationWrapperElement.find('.last-page').removeClass( 'disabled' );
            paginationWrapperElement.find('.prev-page').removeClass( 'disabled' );
            paginationWrapperElement.find('.first-page').removeClass( 'disabled' );

            // Prev to first.
            if ( currentPage <= 1 ) {
                paginationWrapperElement.find('.prev-page').addClass( 'disabled' );
                paginationWrapperElement.find('.first-page').addClass( 'disabled' );
                if ( pagesCount <= 1 ) {
                    paginationWrapperElement.find('.next-page').addClass( 'disabled' );
                    paginationWrapperElement.find('.last-page').addClass( 'disabled' );
                }
            }
            // next to last.
            if ( currentPage >= pagesCount ) {
                paginationWrapperElement.find('.next-page').addClass( 'disabled' );
                paginationWrapperElement.find('.last-page').addClass( 'disabled' );
                if ( pagesCount <= 1 ) {
                    paginationWrapperElement.find('.prev-page').addClass( 'disabled' );
                    paginationWrapperElement.find('.first-page').addClass( 'disabled' );
                }
            }
        }

        async findImagesByPosts( paged = 1, globalLoader = false ) {
            var postTypes = [];

            // CPTs names.
            $('.cpt-name-checkbox:checked').each( (index, element) =>  {
                postTypes.push( $(element).val() );
            });
            var data = {
                paged: paged,
                action: localize_vars.findImagesInPostsAction,
                nonce: localize_vars.nonce,
                cpt_name: postTypes,
            };
            $('.step-4').collapse('hide');
            this.toggleLoader( globalLoader ? '' : 'found-images', 'show' );
            return new Promise( (resolve, reject) => {
                $.ajax({
                    method: 'POST',
                    url: localize_vars.ajaxUrl,
                    data: data,
                    success: ( resp ) => {
                        if ( ! resp.success && resp?.data?.message ) {
                            this.showToast( resp.data.message, 'bg-' + resp.data.status );
                            $('#modal-found-images-watermarks-template').modal('hide');
                            this.toggleLoader( globalLoader ? '' : 'found-images', 'hide' );
                            resolve( resp );
                        }
                        if ( resp?.data?.result ) {
                            var foundImagesResult = resp['data']['result'];
                            var foundImagesModal  = $('#modal-found-images-watermarks-template');
                            var totalItems        = foundImagesResult['attachments_count'];
                            var actionsRow        = foundImagesModal.find('.actions');
                            var totalPages        = Math.ceil( totalItems / 20 );

                            // 1) Fill in the resulted Images HTML.
                            actionsRow.find('.displaying-num').text( totalItems + ' items' );
                            this.updateListFoundImages( foundImagesResult['html'], actionsRow, paged, totalPages, false );

                            // 2) Store the founded images and store them as selected.
                            var foundImagesIDs  = Object.values( foundImagesResult['attachments_ids'] ).map( id => parseInt( id ) );
                            if ( paged === 1 ) {
                                this.selectedImagesCPTs = foundImagesIDs;
                                this.selectedImages     = foundImagesIDs;
                                this.foundImagesPages   = totalPages;
                            }
                            this.foundImages[ paged ] = foundImagesResult['html'];

                            this.toggleSteps();

                        }
                        this.toggleLoader( globalLoader ? '' : 'found-images', 'hide' );
                        resolve( resp );
                    },
                    error: ( err ) => {
                        // console.log( 'error fetching images: ', err );
                        $('.select-errors-dialog').html( err.responseText ).dialog( 'open' );
                        this.toggleLoader( globalLoader ? '' : 'found-images', 'hide' );
                        reject( err );
                    }
                });

            });
        }

        updateSelectedImages( $this ) {
            var imageID = parseInt( $this.data('id') );
            if ( $this.is(':checked' ) ) {
                if ( ! this.selectedImagesCPTs.includes( imageID ) ) {
                    this.selectedImagesCPTs.push( imageID );
                    this.selectedImages = this.selectedImagesCPTs;
                }
            } else {
                var imageIDIndex = this.selectedImagesCPTs.indexOf( imageID );
                if  ( imageIDIndex > -1 ) {
                    this.selectedImagesCPTs.splice( imageIDIndex, 1 );
                    this.selectedImages = this.selectedImagesCPTs;
                }
            }

            this.toggleSteps();
        }

        // ================= //

        showToast( toastMsg, bgColor = 'bg-primary', is_multiple = false ) {
            var toast = $('.' + localize_vars['classes_prefix'] + '-msgs-toast' );
            toast.removeClass( (index, className) => {
                return (className.match (/(^|\s)bg-\S+/g) || []).join(' ');
            });
            var html = '';
            if ( ! is_multiple ) {
                html = '<p>' + toastMsg + '</p>';
            } else {
                html = '<ul class="toast-notice-list">'
                toastHTML.forEach( ( msg ) => {
                    html += '<li class="notice-item"><p>' + msg + '</p></li>';
                });
                html += '</ul>';
            }
            toast.addClass( bgColor ).find('.toast-body').html( html );
            toast.toast('show')
        }

        loading( btn, starting = true ) {
            // All Buttons.
            if ( starting ) {
                btn.addClass('disabled').prop('disabled', true );
                btn.siblings('.spinner').addClass('is-active');
            } else {
                btn.removeClass('disabled').prop('disabled', false );
                btn.siblings('.spinner').removeClass('is-active disabled');
            }
        }

        toggleLoader( cpt = '', action, progress = false ) {
            if ( cpt == '' ) {
                var loader = $('.main-loader');
            } else if ( cpt == 'found-images' ) {
                var loader = $('.selected-images-watermarks-template-modal').find('.loader');
            } else {
                var loader = $('#all-posts-' + cpt ).find('.loader');
            }
            if ( 'show' == action ) {
                loader.show();
            } else if ( 'hide' == action ) {
                loader.hide();
            }

            if ( progress ) {
                loader.find( '.loader-progress').removeClass('d-none');
                loader.find( '.loader-progress-num').removeClass('d-none');
            } else {
                loader.find( '.loader-progress').addClass('d-none');
                loader.find( '.loader-progress-num').addClass('d-none');
            }

        }

        toggleSteps( showSubmit = false ) {
            if ( this.selectedImages.length ) {
                $('.step-2').collapse('show');
            } else {
                $('.step-2').collapse('hide');
            }

            if ( $('.size-item:checked').length && this.selectedImages.length ) {
                $('.step-4').collapse('show');
            } else {
                $('.step-4').collapse('hide');
            }

            if ( $('.size-item:checked').length && $('#select-images-full').is(':checked') ) {
                $('.step-4').collapse('show');
            }
        }
    }
})(jQuery);
