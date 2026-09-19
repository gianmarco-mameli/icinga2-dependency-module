<?php
/*
* Icinga 2 Dependency Module
*
* naming scheme explanation: In order to call functions from frontend, a capital "Action"
* must be present at the end of every function, and there can be no other capitals or underscores in the exposed function name.
*
* Additionally, functions are automatically routed by /module-name/name-of-controller/name-of-function
*
*
* Finally each exposed function requires a .phmtl page of the same name to be present in the /scripts/views folder in order to
* function, unless the function is terminated with 'exit'. this also means that any additional phtml page must have a
* corresponding 'pageAction() {}' function in the module controller in order to be displayed.
*/



namespace Icinga\Module\dependency_plugin\Controllers;
use Icinga\Web\Controller;
use Icinga\Application\Config;
use Icinga\Data\Db\DbConnection as IcingaDbConnection;
use Icinga\Web\Notification;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Navigation\Renderer\BadgeNavigationItemRenderer;
use Icinga\Data\ResourceFactory;
use Exception;


class ModuleController extends Controller{

    private function getConfigPath() {

        return '/etc/icingaweb2/modules/dependency_plugin/config.ini';

    }

    private function getModuleConfigData() {

        $configPath = $this->getConfigPath();

        if(!file_exists(dirname($configPath))){
            throw new Exception('setup');
        }

        if(!file_exists($configPath) || !is_readable($configPath)){
            throw new Exception('Unable to read module config.ini');
        }

        $config = parse_ini_file($configPath, true);

        if($config === false){
            throw new Exception('Unable to parse module config.ini');
        }

        return $config;

    }

    private function getConfigValue($section, $key, $default = null) {

        $config = $this->getModuleConfigData();

        if(isset($config[$section]) && array_key_exists($key, $config[$section])){
            return $config[$section][$key];
        }

        return $default;

    }

    private function getApiSettings() {

        $settings = array(
            'api_host' => $this->getConfigValue('api', 'host'),
            'api_endpoint' => $this->getConfigValue('api', 'port'),
            'api_user' => $this->getConfigValue('api', 'user'),
            'api_password' => $this->getConfigValue('api', 'password')
        );

        foreach($settings as $value){
            if($value === null || $value === ''){
                throw new Exception('API settings are incomplete');
            }
        }

        return $settings;

    }

    private function getDefaultGraphSettings() {

        return array(
            'default_dependency_template' => array('value' => '', 'type' => 'string'),
            'display_up' => array('value' => 'true', 'type' => 'bool'),
            'display_down' => array('value' => 'true', 'type' => 'bool'),
            'display_unreachable' => array('value' => 'true', 'type' => 'bool'),
            'display_only_dependencies' => array('value' => 'true', 'type' => 'bool'),
            'scaling' => array('value' => 'true', 'type' => 'bool'),
            'label_large_nodes' => array('value' => 'true', 'type' => 'bool'),
            'alias_only' => array('value' => 'true', 'type' => 'bool'),
            'text_size' => array('value' => '25', 'type' => 'int'),
            'fullscreen_mode' => array('value' => 'network', 'type' => 'string')
        );

    }

    private function parseGraphSettings($settings) {

        $parsedSettings = array();

        foreach($settings as $name => $setting){
            if($setting['type'] == 'bool'){
                $parsedSettings[$name] = ($setting['value'] === 'true' || $setting['value'] === true || $setting['value'] === '1' || $setting['value'] === 1);
            } else if($setting['type'] == 'int'){
                $parsedSettings[$name] = ((int)($setting['value']));
            } else {
                $parsedSettings[$name] = $setting['value'];
            }
        }

        return $parsedSettings;

    }

    public function statusgridAction(){

        $this->getTabs()->add('Network', array(
            'active'    => false,
            'label'     => $this->translate('Network Map'),
            'url'       => 'dependency_plugin/module/network'
        ));

        $this->getTabs()->add('Hierarchy', array(
            'active'    => false,
            'label'     => $this->translate('Hierarchy Map'),
            'url'       => 'dependency_plugin/module/hierarchy'
        ));

        $this->getTabs()->add('Grid', array(
            'active'    => true,
            'label'     => $this->translate('Grid Map'),
            'url'       => 'dependency_plugin/module/statusGrid'
        ));

    }

    public function hierarchyAction() {

        $this->getTabs()->add('Network', array(
            'active'    => false,
            'label'     => $this->translate('Network Map'),
            'url'       => 'dependency_plugin/module/network'
        ));

        $this->getTabs()->add('Hierarchy', array(
            'active'    => true,
            'label'     => $this->translate('Hierarchy Map'),
            'url'       => 'dependency_plugin/module/hierarchy'
        ));

        $this->getTabs()->add('Grid', array(
            'active'    => false,
            'label'     => $this->translate('Grid Map'),
            'url'       => 'dependency_plugin/module/statusGrid'
        ));

    }

    public function networkAction() {

        $this->getTabs()->add('Network', array(
            'active'    => true,
            'label'     => $this->translate('Network Map'),
            'url'       => 'dependency_plugin/module/network'
        ));

        $this->getTabs()->add('Hierarchy', array(
            'active'    => false,
            'label'     => $this->translate('Hierarchy Map'),
            'url'       => 'dependency_plugin/module/hierarchy'
        ));

        $this->getTabs()->add('Grid', array(
            'active'    => false,
            'label'     => $this->translate('Grid Map'),
            'url'       => 'dependency_plugin/module/statusGrid'
        ));

    }

    public function kickstartAction() {

        $this->getTabs()->add('Graph Settings', array(
            'active'    => false,
            'label'     => $this->translate('Graph Settings'),
            'url'       => 'dependency_plugin/module/settings'
        ));


        $this->getTabs()->add('Module Settings', array(
            'active'    => true,
            'label'     => $this->translate('Module Settings'),
            'url'       => 'dependency_plugin/module/kickstart'
        ));


    }

    public function welcomeAction() {

        $this->getTabs()->add('Welcome', array(
            'active'    => true,
            'label'     => $this->translate('Welcome'),
            'url'       => 'dependency_plugin/module/welcome'
        ));

    }

    public function settingsAction() {

        $this->getTabs()->add('Graph Settings', array(
            'active'    => true,
            'label'     => $this->translate('Graph Settings'),
            'url'       => 'dependency_plugin/module/settings'
        ));

        $this->getTabs()->add('Module Settings', array(
            'active'    => false,
            'label'     => $this->translate('Module Settings'),
            'url'       => 'dependency_plugin/module/kickstart'
        ));

    }

    public function getresourcesAction(){

        $dbArr = [];

        try {

            $resourcesfile = fopen("/etc/icingaweb2/resources.ini", 'r'); //get icinga resources (databases)

            while($line = fgets($resourcesfile)) {

                if(strpos($line, '[') !== false){

                    // echo $line;

                    $dbname = explode('[', $line);
                    $dbname = explode(']', $dbname[1]);
                    array_push($dbArr, $dbname[0]);

                }

            }

            $resources['databases'] = $dbArr;

        } catch (Exception $e){

                header('HTTP/1.1 500 Internal Server Error');
                header('Content-Type: application/json; charset=UTF-8');
                die(json_encode(array('message' => $e->getMessage(), 'code' => '500')));
        }

        echo json_encode($resources);
        fclose($resourcesfile);

        exit;

    }

    function getResource() {

        return $this->getConfigValue('db', 'resource');

    }

    public function getmodulesettingsAction(){

        try {
            $settings = array(
                'resource' => $this->getConfigValue('db', 'resource', ''),
                'host' => $this->getConfigValue('api', 'host', ''),
                'port' => $this->getConfigValue('api', 'port', ''),
                'username' => $this->getConfigValue('api', 'user', ''),
                'password' => $this->getConfigValue('api', 'password', '')
            );
        } catch(Exception $e){
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(array('message' => $e->getMessage(), 'code' => '500')));
        }

        echo json_encode($settings);

        exit;

    }

    public function storesettingsAction(){


    //  this function uses a built-in icinga web function saveIni(); which automatically saves any passed data to
    //  /etc/icingaweb2/modules/name-of-moudle/config.ini

        $json = $_POST["json"];

        $data = json_decode($json, true);

        // var_dump($data);

        // die;

        if($data != null){

            $resource = $data[0]['value'];
            $host = $data[1]['value'];
            $port = $data[2]['value'];
            $username = $data[3]['value'];
            $password = $data[4]['value'];


            try {
            $config = $this->config();
            $config->setSection('db', array('resource' => $resource));
            $config->setSection('api', array(
                'host' => $host,
                'port' => $port,
                'user' => $username,
                'password' => $password
            ));

            $config->saveIni();
            } catch (Exception $e) {
                header('HTTP/1.1 500 Internal Server Error');
                header('Content-Type: application/json; charset=UTF-8');

                die(json_encode(
                    array(
                    'message' => "Error Saving Settings To config.ini",
                    'code' => '500',
                    'action'=>'setup'
                     )
                    ));

            }

        echo true;
        }

        exit;

    }

    public function getdependencyAction() {

        try {

            $vals = $this->getApiSettings();
        }
        catch(Exception $e){

                header('HTTP/1.1 500 Internal Server Error');
                header('Content-Type: application/json; charset=UTF-8');

                die(json_encode(array('message' => $e->getMessage(), 'code' => '500')));
        }

            $request_url = 'https://' . $vals['api_host'] . ':'. $vals['api_endpoint'] . '/v1/objects/dependencies';
            $username = $vals['api_user'];
            $password = $vals['api_password'];
            $headers = array(
                'Accept: application/json',
                'X-HTTP-Method-Override: GET'
            );

            $ch = curl_init();

            curl_setopt_array($ch, array(

                CURLOPT_URL => $request_url,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_USERPWD => $username . ":" . $password,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ));

            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if($code === 401){ //echo detailed errors.
                header('HTTP/1.1 401 Unauthorized');
                header('Content-Type: application/json; charset=UTF-8');
                die(json_encode(array('message' => 'Unauthorized, Please Check Entered Credentials', 'code' => $code)));
            }else if($code != 200){
                header('HTTP/1.1 500 Internal Server Error');
                header('Content-Type: application/json; charset=UTF-8');
                die(json_encode(array('message' => curl_error($ch), 'code' => $code)));
            }

            echo $response;
            exit;

}

   public function gethostsAction(){

        try {

            $vals = $this->getApiSettings();
        }
        catch(Exception $e){

                header('HTTP/1.1 500 Internal Server Error');
                header('Content-Type: application/json; charset=UTF-8');
                die(json_encode(array('message' => $e->getMessage(), 'code' => "500")));
        }

            $request_url = 'https://' . $vals['api_host'] . ':'. $vals['api_endpoint'] . '/v1/objects/hosts';
            $username = $vals['api_user'];
            $password = $vals['api_password'];
            $headers = array(
                'Accept: application/json',
                'X-HTTP-Method-Override: GET'
            );

            $ch = curl_init();

            curl_setopt_array($ch, array(

                CURLOPT_URL => $request_url,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_USERPWD => $username . ":" . $password,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ));

            $response = curl_exec($ch);

            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if($code === 401){
                header('HTTP/1.1 401 Unauthorized');
                header('Content-Type: application/json; charset=UTF-8');
                die(json_encode(array('message' => 'Unauthorized, Please Check Entered Credentials', 'code' => $code)));
            }else if($code != 200){
                header('HTTP/1.1 500 Internal Server Error');
                header('Content-Type: application/json; charset=UTF-8');
                die(json_encode(array('message' => curl_error($ch), 'code' => $code)));
                // echo json_encode($code );
                die;
            }
            echo $response;
            exit;

}

    public function storenodepositionsAction(){


        $resource = $this->getResource();

        $db = IcingaDbConnection::fromResourceName($resource)->getDbAdapter();

        $json = $_POST["json"];

        $data = json_decode($json, true);

        if($data == 'RESET'){
            $db->exec("TRUNCATE TABLE node_positions;");
        }

        else if($data != null){

           $result = $db->exec("TRUNCATE TABLE node_positions;");

            foreach($data as $item){

                $name = $item['id'];
                $node_x = $item['x'];
                $node_y = $item['y'];

                echo(gettype($item['y']));

                $res = $db->insert('node_positions', array(
                    'node_name'=> $name, 'node_x' => $node_x, 'node_y' => $node_y
                ));


                if(!$res){
                echo "An error occured while attempting to store nodes.\n";
                exit;
                }
            }


        }

        exit;
    }

    public function getnodesAction(){

        try {

            $resource = $this->getResource();

            $db = IcingaDbConnection::fromResourceName($resource)->getDbAdapter();

            $query = 'SELECT * from node_positions';
            $vals = $db->fetchAll($query);

            if(!$vals){
                    throw new Exception('Empty Table');
            }

        } catch(Exception $e){

            if($e-> getMessage() == 'Empty Table'){

                $json = json_encode('EMPTY!');

                echo $json;

                exit;

            } else {
                header('HTTP/1.1 500 Internal Server Error');
                header('Content-Type: application/json; charset=UTF-8');
                die(json_encode(array('message' => $e->getMessage(), 'code' => '500')));
            }
        }

            $json = json_encode($vals);

            echo $json;

            exit;
    }

    public function storegraphsettingsAction(){

        $json = $_POST["json"];

        $data = json_decode($json, true);

        if($data != null){

            $settings = $this->getDefaultGraphSettings();

            foreach($data as $name => $setting){
                if(array_key_exists($name, $settings)){
                    $settings[$name]['value'] = $setting['value'];
                }
            }

            try {
                $configSettings = array();

                foreach($settings as $name => $setting){
                    $configSettings[$name] = $setting['value'];
                }

                $config = $this->config();
                $config->setSection('graph', $configSettings);
                $config->saveIni();
            } catch(Exception $e){
                header('HTTP/1.1 500 Internal Server Error');
                header('Content-Type: application/json; charset=UTF-8');
                die(json_encode(array('message' => $e->getMessage(), 'code' => '500')));
            }

            echo true;
        }

       exit;
    }

    public function getgraphsettingsAction() {

        try {

            $vals = $this->getDefaultGraphSettings();
            $config = $this->getModuleConfigData();

            if(isset($config['graph'])){
                foreach($config['graph'] as $name => $value){
                    if(array_key_exists($name, $vals)){
                        $vals[$name]['value'] = $value;
                    }
                }
            }
        } catch(Exception $e){

                header('HTTP/1.1 500 Internal Server Error');
                header('Content-Type: application/json; charset=UTF-8');
                die(json_encode(array('message' => $e->getMessage(), 'code' => '500')));

            exit;
         }

            $parsedSettings = $this->parseGraphSettings($vals);

            $json = json_encode($parsedSettings);

            echo $json;

            exit;
    }


}

?>
