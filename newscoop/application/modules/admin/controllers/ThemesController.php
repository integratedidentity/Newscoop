<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
use Newscoop\Entity\Resource;
use Newscoop\Controller\Action\Helper\Datatable\Adapter\Theme,
    Newscoop\Controller\Action\Helper\Datatable\Adapter\ThemeFiles,
    Newscoop\Service\IPublicationService,
    Newscoop\Service\IThemeManagementService,
    Newscoop\Service\Resource\ResourceId, 
    Newscoop\Service\IThemeService, 
    Newscoop\Service\Model\SearchTheme,
    Newscoop\Service\PublicationServiceDoctrine,
    Newscoop\Entity\Theme\Loader\LocalLoader,
    Newscoop\Service\IOutputService,
    Newscoop\Service\Exception\DuplicateNameException,
    Newscoop\Entity\Output
    ;

/**
 * Themes Controller
 */
class Admin_ThemesController extends Zend_Controller_Action
{

    /**
     * No idea what this should be
     * @var unknown_type
     */
    private $_repository;

    /** 
     * @var Newscoop\Services\Resource\ResourceId 
     */
    private $_resourceId = NULL;

    /** 
     * @var Newscoop\Service\IThemeManagementService 
     */
    private $_themeService = NULL;

    /** 
     * @var Newscoop\Service\IPublicationService 
     */
    private $_publicationService = NULL;
    
    /** 
     * @var Newscoop\Service\ThemeServiceLocalFileSystem 
     */
    private $_themeFileService = NULL;
    
    /**
     * @var Newscoop\Service\IOutputService 
     */
    private $_outputService = NULL;
    
    /**
     * Provides the controller resource id.
     *
     * @return Newscoop\Services\Resource\ResourceId
     * The controller resource id.
     */
    public function getResourceId()
    {
        if( $this->_resourceId === NULL ) {
            $this->_resourceId = new ResourceId( __CLASS__ );
        }
        return $this->_resourceId;
    }

    /**
     * Provides the theme service.
     * 
     * @return Newscoop\Service\IThemeManagementService
     */
    public function getThemeService()
    {
        if( $this->_themeService === NULL ) {
            $this->_themeService = $this->getResourceId()->getService( IThemeManagementService::NAME_1 );
        }
        return $this->_themeService;
    }
    
	/**
     * Provides the publication service
     *
     * @return Newscoop\Service\ThemeServiceLocalFileSystem
     * The publication service to be used by this controller.
     */
    public function getThemeFileService( )
    {
        if( $this->_themeFileService === NULL ) {
            $this->_themeFileService = $this->getResourceId()->getService( IThemeService::NAME );
        }
        return $this->_themeFileService;
    }
    
	/**
	 * Provides the ouput service.
	 *
	 * @return Newscoop\Service\IOutputService
	 *		The service service to be used by this controller.
	 */
	public function getOutputService()
	{
		if ($this->_outputService === NULL) {
			$this->_outputService = $this->getResourceId()->getService( IOutputService::NAME );
		}
		return $this->_outputService;
	}
	
	/**
     * Provides the publication service
     *
     * @return Newscoop\Service\IPublicationService
     * The publication service to be used by this controller.
     */
    public function getPublicationService()
    {
        if( $this->_publicationService === NULL ) {
            $this->_publicationService = $this->getResourceId()->getService( IPublicationService::NAME );
        }
        return $this->_publicationService;
    }

    public $instId = null;
    public function init()
    {
        $this->getThemeService();
        $this->view->placeholder( 'title' )->set( getGS( 'Theme management' ) );
        $this->_helper->contextSwitch
            ->addActionContext( 'index', 'json' )
            ->addActionContext( 'assign-to-publication', 'json' )
            ->addActionContext( 'output-edit', 'json' )
            ->initContext();
    }
    

    public function indexAction()
    {
        $datatableAdapter = new Theme( $this->getThemeService() );
        // really wierd way to bind some filtering logic right here
        // basically this is the column index we are going to look for filtering requests
        $datatableAdapter->setPublicationFilterColumn(4);
        
        $datatable = $this->_helper->genericDatatable;
        /* @var $datatable Action_Helper_GenericDatatable */
        $datatable->setAdapter( $datatableAdapter )->setOutputObject( $this->view );

        $view = $this->view;
        $datatable            // setting options for the datatable
            ->setCols( array
            (
                'checkbox'	   => '',
                'image'        => '',
                'name'         => getGS( 'Theme name / version' ),
                'description'  => getGS( 'Compatibility' ),
                'actions'      => ''
            ))
            ->buildColumnDefs()
            ->setOptions( array
            (
                'sAjaxSource' => $this->view->url( array( 'action' => 'index', 'format' => 'json') ),
            	'sPaginationType' => 'full_numbers',
            	'bServerSide'    => true,
            	'bJQueryUI'      => true,
            	'bAutoWidth'     => false,
                'sDom'		     => 'tiprl',
            	'iDisplayLength' => 25,
            	'bLengthChange'  => false,
                'fnRowCallback'	 => "newscoopDatatables.callbackRow",
                'fnDrawCallback' => "newscoopDatatables.callbackDraw",
                'fnInitComplete' => "newscoopDatatables.callbackInit"
            ) )
            ->setWidths( array( 'checkbox' => 20, 'image' => 215, 'name' => 235, 'description' => 280, 'actions' => 115 ) )
            ->setRowHandler
            ( 
                function( $theme, $index = null )
                {
                    return array
                    ( 
                    	"id"       => $theme['id'], 
                    	"images"   => $theme['images'],
                        "title"    => $theme['title'], 
                        "designer" => $theme['designer'], 
                        "version"  => $theme['version'],
                    	"compat"   => $theme['subTitle'],
                    	"text"     => $theme['description']   
                    );
                } 
            )
            /*
            ->setDataMap( array
            (
                "checkbox"     => null,
            	'image'        => null,
                'name'         => null,
                'description'  => null,
                'actions'      => null,
            ))
            */
            ->setParams( $this->_request->getParams() );
            
        if( ( $this->view->mytable = $datatable->dispatch() ) )
        {
            $this->view->publications  = $this->getPublicationService()->getEntities();
            
            $this->view->headScript()->appendFile( $this->view->baseUrl( "/js/jquery/jquery.tmpl.js" ) );
            $this->view->headLink( array
            ( 
            	'type'  =>'text/css', 
            	'href'  => $this->view->baseUrl('/admin-style/themes_list.css'),
                'media'	=> 'screen',
                'rel'	=> 'stylesheet'
            ) );
        }
    }
    
    function editAction()
    {
        $themeId = $this->_request->getParam( 'id' );
        $thmServ = $this->getThemeService();
        $theme   = $thmServ->findById( $themeId );
        $outServ = $this->getOutputService();
        foreach( ( $outputs = $outServ->getEntities() ) as $k => $output )
            $outSets[] = $thmServ->findOutputSetting( $theme, $output ); // ->toObject()
        
        $themeForm = new Admin_Form_Theme();
        $themeForm->populate( array
        ( 
        	"theme-version"    => (string) $theme->getVersion(),
        	"required-version" => (string) $theme->getMinorNewscoopVersion() 
        ) );
        
        
        $this->view->headLink( array
        ( 
        	'type'  =>'text/css', 
        	'href'  => $this->view->baseUrl('/admin-style/common.css'),
            'media'	=> 'screen',
            'rel'	=> 'stylesheet'
        ) );
        
        $this->view->jQueryUtils()
            ->registerVar
            ( 
                'load-output-settings-url', 
                $this->_helper->url->url( array
                ( 
                	'action' => 'output-edit', 
                	'controller' => 'themes',
                    'module' => 'admin', 
                    'themeid' => '$1', 
                    'outputid' => '$2'  
                ), null, true, false ) 
            );
        $this->view->themeForm      = $themeForm;
        $this->view->theme          = $theme->toObject();
        $this->view->outputs        = $outputs;
        $this->view->outputSettings = $outSets;
    }
    
    public function outputEditAction()
    {
        $thmServ    = $this->getThemeService();
        
        // getting the theme entity
        $themeId    = $this->_request->getParam( 'themeid' );        
        $theme      = $thmServ->findById( $themeId );
        
        // getting selected output 
        $outputId   = $this->_request->getParam( 'outputid' );
        $output     = $this->getOutputService()->getById( $outputId );
        /* @var $settings Newscoop\Entity\Output */
        
        // getting all available templates
        $templates  = array();
        foreach( $thmServ->getTemplates($theme) as $tpl )
            $templates[ $tpl->getPath() ] = $tpl->getName();
            
        // making the form 
        $outputForm = new Admin_Form_Theme_OutputSettings();
        $outputForm->setAction( $this->_helper->url( 'output-edit' ) );
         
        // getting theme's output settings
        $settings   = $thmServ->findOutputSetting( $theme, $output );
        /* @var $settings Newscoop\Entity\OutputSettings */
        $settingVals= array
        ( 
        	"frontpage"   => $settings->getFrontPage(),
        	"articlepage" => $settings->getArticlePage(),
        	"sectionpage" => $settings->getSectionPage(),
        	"errorpage"   => $settings->getErrorPage(),
            "outputid"	  => $outputId,
            "themeid"	  => $themeId
        );
        $outputForm->setValues( $templates, $settingVals );
        
        try // @todo maybe implement this a little smarter, little less code?  
        {
            if( $this->_request->isPost() ) {
                if( $outputForm->isValid( $this->_request->getPost() ) )
                {
                    $settings->setFrontPage( new Resource( $outputForm->getValue( 'frontpage' ) ) );
                    $settings->setSectionPage( new Resource( $outputForm->getValue( 'sectionpage' ) ) );
                    $settings->setArticlePage( new Resource( $outputForm->getValue( 'articlepage' ) ) );
                    $settings->setErrorPage( new Resource( $outputForm->getValue( 'errorpage' ) ) );
                    $this->getThemeService()->assignOutputSetting( $settings, $theme );
                    
                    $this->_helper->flashMessenger( ( $this->view->success = getGS( 'Settings saved.' ) ) );
                }
                else
                {
                    throw new \Exception();
                }
            }
        }
        catch( \Exception $e )
        {
            $this->_helper->flashMessenger( ( $this->view->error = getGS( 'Saving settings failed.' ) ) );
        }
        $this->view->outputForm = $outputForm;
        
        // disabling layout for ajax and hide the submit button
        if( $this->_request->isXmlHttpRequest() )
        {
            $this->_helper->layout->disableLayout();
            $outputForm->getElement( 'submit' )
                ->clearDecorators()
                ->setAttrib( 'style', 'display:none' );
        }
    }
    
    public function filesAction()
    {
        $datatable = $this->_helper->genericDatatable; 
        $datatable->setAdapter
        ( 
            new ThemeFiles( $this->getThemeFileService(), $this->_request->getParam( 'id' ) ) 
        )->setOutputObject( $this->view );
    }
    
    function assignToPublicationAction()
    {
        try
        {
            $theme  = $this->getThemeService()->getById( $this->_request->getParam( 'theme-id' ) );
		    $pub    = $this->getPublicationService()->findById( $this->_request->getParam( 'pub-id' ) );
    		$this->view->response = $this->getThemeService()->assignTheme( $theme, $pub );
        }
        catch( DuplicateNameException $e )
        {
            $this->view->exception = array( "code" => $e->getCode(), "message" => getGS( 'Duplicate assignation' ) );
        }
        catch( \Exception $e )
        {
            $this->view->exception = array( "code" => $e->getCode(), "message" => getGS( 'Something broke' ) );
        }
        
    }

    public function installAction()
    {
        $this->_repository->install( $this->_getParam( 'offset' ) );
        $this->_helper->entity->flushManager();
        
        $this->_helper->flashMessenger( getGS( 'Theme $1', getGS( 'installed' ) ) );
        $this->_helper->redirector( 'index' );
    }

    public function uninstallAction()
    {
        $this->_repository->uninstall( $this->_getParam( 'id' ) );
        $this->_helper->entity->flushManager();
        
        $this->_helper->flashMessenger( getGS( 'Theme $1', getGS( 'deleted' ) ) );
        $this->_helper->redirector( 'index' );
    }
}

