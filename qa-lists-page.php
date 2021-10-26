<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    class qa_lists_page
    {

        private $directory;
        private $urltoroot;

 // for display in admin interface under admin/pages
        function suggest_requests()
        {
                return array(
                                array(
                                        'title' => 'User Lists', // title of page
                                        'request' => 'userlists', // request name
                                        'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
                                     ),
                            );
        }

        public function load_module( $directory, $urltoroot )
        {
            $this->directory = $directory;
            $this->urltoroot = $urltoroot;
        }

        public function match_request( $request )
        {
            $requestparts = qa_request_parts();

            return ( !empty( $requestparts )
                && @$requestparts[ 0 ] === 'userlists'
                && !empty( $requestparts[ 1 ] )
            );
        }

        public function process_request( $request )
        {
            $handle = qa_request_part( 1 );

            if ( !strlen( $handle ) ) {
                $handle = qa_get_logged_in_handle();
                qa_redirect( isset( $handle ) ? 'user/' . $handle : 'users' );
            }

            if ( QA_FINAL_EXTERNAL_USERS ) {
                $userid = qa_handle_to_userid( $handle );
                if ( !isset( $userid ) )
                    return include QA_INCLUDE_DIR . 'qa-page-not-found.php';

                $usershtml = qa_get_users_html( array( $userid ), false, qa_path_to_root(), true );
                $userhtml = @$usershtml[ $userid ];

            } else
                $userhtml = qa_html( $handle );

//            qa_set_template( 'questions' );

            return require dirname( __FILE__ ) . '/qa-lists.php';
            //return require QA_EXAM_DIR . '/pages/exams.php';
        }
    }


    /*
        Omit PHP closing tag to help avoid accidental output
    */
