<?php

class PagesController extends ControllerAdmin
{
    public function createAction()
    {
        $this->tag->setTitle('Create Page');

        $form = new Pages_PagesForm();

        $this->view->setVar('pageForm', $form);

        parent::initialize();
    }

    public function addlocalizationAction( $id = null )
    {
        if ($this->request->isPost() == true)
        {
            $form = new Pages_LocalizationForm;

            // Validate the form
            $data = $this->request->getPost();

            if ( !$form->isValid( $this->request->getPost() ) )
            {
                foreach ( $form->getMessages() as $key => $message )
                {
                    $this->flash->error( $message );
                }

                return $this->response->redirect("pages/addlocalization/" . $data['page_id']);
            }

            $info = new PagesInfo();

            $info->title = $data['title'];
            $info->page_id = $data['page_id'];
            $info->content = $data['content'];
            $info->meta_title = $data['title'];
            $info->meta_keywords = $data['metaKeywords'];
            $info->meta_description = $data['metaDescription'];

            if ( $info->save() != false )
            {
                $pLang = new PagesLangs();

                $pLang->page_id = $data['page_id'];
                $pLang->info_id = $info->id;
                $pLang->lang_id = $data['lang'];

                $pLang->save();

                return $this->response->redirect( 'pages/edit/' . $data['page_id'] . '/' . $data['lang'] );
            }
        }

        $langDB = PagesLangs::find(array(
            'conditions' => 'page_id = ?1',
            'bind'  => array( 1 => $id)
        ));

        $this->tag->setTitle('Add localization');

        $this->view->setVars(
            array(
                'id' => $id,
                'pageForm' => new Pages_LocalizationForm( $langDB )
            )
        );

        parent::initialize();
    }

    public function listAction()
    {
        $this->tag->setTitle('List of all pages');

        $pages = Pages::find();

        $this->view->setVars(array('pages' => $pages));

        parent::initialize();
    }

    public function editAction( $idGet, $langGet )
    {
        $this->tag->setTitle('Edit page');

        $pageDB = Pages::getPage( $idGet, $langGet );

        if ( $pageDB != false )
        {
            $this->view->setVars(
                array(
                    'page' => $pageDB,
                    'pageForm' => new Pages_PagesForm( $pageDB, true )
                )
            );
        }

        parent::initialize();
    }

    public function deleteAction( $id )
    {
        $page = Pages::findFirst( $id );

        if ( $page != false )
        {
            $page->PagesInfo->delete();

            $page->PagesLangs->delete();

            $page->delete();
        }

        return $this->responce->redirect('/pages/list/');
    }

    public function addAction()
    {
        if ($this->request->isPost() != true)
        {
            return $this->response->redirect( 'pages/create/' );
        }

        $form = new Pages_PagesForm;

        // Validate the form
        $data = $this->request->getPost();

        if ( !$form->isValid( $this->request->getPost() ) )
        {
            foreach ( $form->getMessages() as $key => $message )
            {
                $this->flash->error( $message );
            }

            return $this->dispatcher->forward(["action" => "create", 'params' => array('pages') ]);
        }

        $page = new Pages();

        $page->location = $data['location'];
        $page->type = $data['type'];
        $page->status = 0;
        $page->position = 0;
        $page->name = $data['name'];

        if ( $page->save() != false )
        {
            $info = new PagesInfo();

            $info->title = $data['title'];
            $info->page_id = $page->id;
            $info->content = $data['content'];
            $info->meta_title = $data['title'];
            $info->meta_keywords = $data['metaKeywords'];
            $info->meta_description = $data['metaDescription'];

            if ( $info->save() != false )
            {
                $pLang = new PagesLangs();

                $pLang->page_id = $page->id;
                $pLang->info_id = $info->id;
                $pLang->lang_id = $data['lang'];

                $pLang->save();

                return $this->response->redirect( 'pages/edit/' . $page->id . '/' . $data['lang'] );
            }
            else
            {
                echo "Error, can't store page info right now: \n";
                foreach ( $page->getMessages() as $message ) {
                    echo $message, "\n";
                }
            }
        }
        else
        {
            echo "Error, can't store page right now: \n";
            foreach ( $page->getMessages() as $message ) {
                echo $message, "\n";
            }
        }
    }

    public function saveAction()
    {
        if ( $this->request->isPost() != true)
        {
            return $this->response->redirect( 'pages/list/' );
        }

        $form = new Pages_PagesForm;

        // Validate the form
        $data = $this->request->getPost();

        if ( !$form->isValid( $this->request->getPost() ) )
        {
            foreach ( $form->getMessages() as $key => $message )
            {
                $this->flash->error( $message );
            }
            return $this->forward( '/pages/create/' );
        }

        $page = Pages::findFirst( $data['id'] );

        $page->location = $data['location'];
        $page->type = $data['type'];
        $page->name = $data['name'];

        $langDB = PagesLangs::findFirst(array(
            'conditions' => 'page_id = ?1 AND lang_id = ?2',
            'bind'  => array( 1 => $data['id'], 2 => $data['lang'] )
        ));

        $info = PagesInfo::findFirst(array(
            "conditions" => "id = ?1",
            "bind"       => array( 1 => $langDB->info_id ),
            //"cache"      => array( "key" => 'catalog'.$url, "lifetime" => 172800 )
        ));

        $info->title = $data['title'];
        $info->content = $data['content'];
        $info->meta_title = $data['title'];
        $info->meta_keywords = $data['metaKeywords'];
        $info->meta_description = $data['metaDescription'];
        $info->save();

        return $this->response->redirect( 'pages/edit/' . $page->id . '/' . $data['lang'] );
    }

    public function uploadAction()
    {
        if ( !$this->request->isPost() )
        {
            return NULL;
        }

        /** @var \Phalcon\Config $config */
        $config = $this->getDI()->get('config');

        if ( !$this->request->hasFiles() )
        {
            return NULL;
        }

        $uploads = $this->request->getUploadedFiles();

        foreach ( $uploads as $key => $upload )
        {
            $path = $config->media->uploadPath . md5(uniqid(rand(), true)).'-'.strtolower($upload->getname());

            if ( $upload->moveTo($path) )
            {
                $result = 'ok';

                $mImage = Images::findFirst(array(
                    "conditions" => "ovner_id = ?1 AND main = 1",
                    "bind"       => array( 1 => $this->request->getPost('ovner') )
                ));

                $image = new Images();

                $image->ovner_id = $this->request->getPost('ovner');
                $image->url = $path;
                $image->main = ( !empty($mImage) ) ? '0' : '1';
                $image->position = $key;

                $image->save();
            }
        }

        return $result;
    }
}