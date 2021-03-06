<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class form_cadastro extends MY_Controller{
    
    function __construct(){
      parent::__construct();
      
      $this->load->model('form_cadastro_model');
      $this->load->model('usuario_model');
      $this->load->model('form_model');
      $this->session->set_userdata( 'check_id', $this->form_cadastro_id );
      $this->output->enable_profiler(TRUE);
    }

    public function index(){

      
      $this->crud->set_table( 'form' );

      $this->crud->columns('descricao','sigla','status');
        
      $this->crud->fields('descricao','sigla','status');
    
      if( $this->adm ){
    
        $this->crud->add_action('Mostrar', '', 'form_cadastro/form/add','read-icon');
        $this->crud->unset_delete();
        $this->crud->unset_edit();
        $this->crud->unset_add();
        $this->crud->unset_read();
      }else{

          if( $this->usuario_model->validarUsuario_nome( $this->usuarios_id ) ){
            
         
              redirect('form_cadastro/form');
            
          }else{

            redirect('usuarios');  
          }
      }

      $this->load->vars($this->crud->render());
      $this->load->view( 'gerenciar');
    }


    public function form(){

     
      try {
        
        if( $this->adm ){
          $this->form_id    = $this->uri->segment(4);
          $this->form_sigla = $this->form_model->getField($this->form_id,'sigla');
          $this->form_nome  = $this->form_model->getField($this->form_id,'descricao');

          $this->session->set_userdata( 'form_id'   , $this->form_id );
          $this->session->set_userdata( 'form_sigla', $this->form_sigla );
          $this->session->set_userdata( 'form_nome' , $this->form_nome );
          $this->crud->unset_back_to_list();
        } else {
        
          $this->crud->unset_print();
          $this->crud->unset_export();
          $this->crud->unset_delete();
          
          if( $this->form_cadastro_id ){

              $this->crud->unset_add();            
          }
          $this->crud->where( 'usuarios_id', $this->usuarios_id  );
          $this->crud->where( 'form_id'   , $this->form_id  );
        }
 


        $form_existe  = $this->form_model->getForm_existe( $this->form_sigla );

        if( !$form_existe ){
          $this->session->set_flashdata('mensagem',
          '<div class="alert alert-danger">Atenção: O Formulário '. $this->form_sigla. ' ainda não existe!</div>');
          redirect('form_cadastro');
        }
            

        $tabela        = $this->form_sigla;
        $tabela_campos = 'campos';
     

        $this->crud->set_subject( 'Fazer Inscrição no '.$this->form_sigla );
        
        $this->crud->set_table( $tabela );

        $detalhes = array();
        $detalhes = $this->form_cadastro_model->getFieldsLabelRules($this->form_id,"field,label", 0,1,$tabela_campos);

        for($i=0;$i < count($detalhes);$i++){
            $this->crud->set_relation_n_n( $detalhes[$i]["label"], $tabela.'_'.$detalhes[$i]["field"]      ,$tabela.'_'.$detalhes[$i]["field"],
                                                    $tabela.'_id', $detalhes[$i]["field"].'_id','descricao',
                                                    null, array( 'form_id' => $this->form_id));
        }
   
        $this->crud->columns        ( $this->form_cadastro_model->getFields($this->form_id, 0, 1, 0  ,$tabela_campos ));
        $this->crud->fields         ( $this->form_cadastro_model->getFields($this->form_id, 0, 0, 1  ,$tabela_campos ));
        $this->crud->required_fields( $this->form_cadastro_model->getFields($this->form_id, 1, 0, 0  ,$tabela_campos ));
       
        $this->crud->set_rules( $this->form_cadastro_model->getFieldsLabelRules($this->form_id,"field,label,rules", 0,0,$tabela_campos) );
        
        $this->crud->field_type( 'usuarios_id'   , 'hidden', $this->usuarios_id );
        $this->crud->field_type( 'form_id'      , 'hidden', $this->form_id );
        $this->crud->field_type( 'data_cadastro', 'hidden');
        
        $this->set_rules();

        $upload = array();
        $upload = $this->form_cadastro_model->getFieldsLabelRules($this->form_id,"field,upload", 1,0,$tabela_campos);
        
        for($i=0;$i<count($upload);$i++){
          $this->crud->set_field_upload( $upload[$i]["field"], 'assets/uploads/files');  
        }
            
        $display = array();
        $display = $this->form_cadastro_model->getFieldsLabelRules($this->form_id,"field,label", 1,0,$tabela_campos);

        for($i=0;$i<count($display);$i++){
           $this->crud->display_as($display[$i]["field"],$display[$i]["label"]);  
        }
       
        $this->crud->callback_before_update(array($this,'before_insert_update'));
        $this->crud->callback_before_insert(array($this,'before_insert_update'));
        
        $this->crud->callback_after_update(array($this, 'after_update'));
        $this->crud->callback_after_insert(array($this, 'after_insert'));
        
        $this->crud->callback_after_delete(array($this, 'after_delete'));

        $this->crud->set_rules('usuarios_id','form_cadastro','callback_before_insert');
      
        $this->load->vars($this->crud->render());
        $this->load->view( 'gerenciar' );
      
      } catch(Exception $e) {
          fb::info($e->getMessage().' --- '.$e->getTraceAsString());
      }     
    }

    private function set_rules(){

      $this->crud->set_rules('usuarios_id','usuario','required');
      $this->crud->set_rules('form_id','Formulário','required');
      if ( $this->crud->getState() == 'insert' || $this->crud->getState() == 'insert_validation'){
           $this->crud->set_rules('usuarios_id','usuarios','callback_unique_form_cadastro['.$this->input->post('form_id').']');
      }
      $this->crud->set_rules('form_id','Formulários','callback_checar_campos['.$this->input->post('form_id').']');
    }

    public function unique_form_cadastro( $pk1, $pk2 ){
      $this->db->where('usuarios_id', $pk1);
      $this->db->where('form_id'   , $pk2);
      if($this->db->count_all_results($this->form_sigla) != 0) {
        $this->form_validation->set_message('unique_form_cadastro','Já existe uma inscrição registrada para o usuario.');
        return false;
      } else {
        return true;
      }
    }

    public function checar_campos($pk1, $pk2) {
      
      $this->db->where('form_id', $pk2);
      if( $this->db->count_all_results('campos') < 1 ) {
        $this->form_validation->set_message('checar_campos','Cadastre pelo menos um campo para este Formulário.');
        return false;
      } else {
        return true;
      }

    }

    public function before_insert_update( $array_post ) {
  
      $array_post['usuarios_id']    = $this->usuarios_id;
      $array_post['form_id']       = $this->form_id;
      $array_post['data_cadastro'] = date('Y-m-d H:i:s');
      return $array_post;

    }

    public function after_insert( $post_array, $primary_key ) {
        $this->session->set_userdata( 'form_cadastro_id', $primary_key );
        $this->form_cadastro_id = $this->session->set_userdata( 'form_cadastro_id', $primary_key );
        return $this->db->insert('user_logs', array('usuarios_id' => $this->usuarios_id,'form_id' => $primary_key,'action'=>'insert', 'data' => date('Y-m-d H:i:s')));
    }

    public function after_update( $post_array, $primary_key ) {
        $this->session->set_userdata( 'form_cadastro_id', $primary_key );
        $this->form_cadastro_id = $this->session->set_userdata( 'form_cadastro_id', $primary_key );
        return $this->db->insert('user_logs', array('usuarios_id' => $this->usuarios_id,'form_id' => $primary_key,'action'=>'update', 'data' => date('Y-m-d H:i:s')));
    }


    public function after_delete( $primary_key ) {
        $this->session->set_userdata( 'form_cadastro_id', 0 );
        $this->form_cadastro_id = 0;
        return $this->db->insert('user_logs', array('usuarios_id' => $this->usuarios_id,'form_id' => $primary_key,'action'=>'delete', 'data' => date('Y-m-d H:i:s')));
    }

  }

