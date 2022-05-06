<?php

class ControllerAdmServico
{

    private $_method;

    private $_model_servico;

    private $_id_servico;

    private $_id_empresa;

    private $_dados_requisicao;
    private $_array_funcionarios;
    private $_array_genero;
    private $_array_tipos_atendimento;

    public function __construct($model_servico)
    {
        $this->_model_servico = $model_servico;

        $this->_method = $_SERVER['REQUEST_METHOD'];

        //PEGANDO DADOS DA REQ
        $json = file_get_contents("php://input");
        $this->_dados_requisicao = json_decode($json);

        $this->_flag =  $_GET["acao"] ?? $this->_dados_requisicao->acao ?? $_POST["acao"] ?? null;
        $this->_id_servico =  $_GET["id_servico"] ?? $this->_dados_requisicao->id_servico ?? $_POST["id_servico"] ?? null;

        $this->_id_empresa =  $_GET["id_empresa"] ?? $this->_dados_requisicao->id_empresa ?? $_POST["id_empresa"] ?? null;
    }

    function router()
    {

        switch ($this->_method) {
            case 'GET':

                if ($this->_id_empresa != null && $this->_flag == "listarEspecialidades") {

                    return $this->_model_servico->getEspecialidades();
                } elseif ($this->_id_empresa != null && $this->_flag == "listarEspecialidadePartesCorpo") {

                    return $this->_model_servico->getEspecialidadesPartesCorpo();
                } elseif ($this->_id_empresa != null && $this->_flag == "listarCategoriaServicos") {

                    return $this->_model_servico->getServicosEmpresaByCategoria();
                } elseif ($this->_id_empresa != null && $this->_flag == "listarDetalhesServico") {

                    return $this->_model_servico->getDetalhesServico();
                }

                break;

            case 'POST':

                if ($this->_flag == "createServico") {

                    if (isset($_POST["envio_form"])) {

                        $envio_form = $_POST["envio_form"];

                        if ($envio_form == "true") {

                            if (isset($_FILES["imagem_servico"])) {
                                if ($_FILES["imagem_servico"]["error"] == 4) {
                                    return ("chegou a req sem imagem de serviço");
                                } else {
                                    return $this->_model_servico->updateServico($this->_id_servico);
                                }
                            }
                        }
                    } else {

                        $this->_id_servico = $this->_model_servico->createServico();
                        $dados_servico["dados_servico"] = $this->_model_servico->addEspecialidadePartesCorpo($this->_id_servico);

                        $this->_array_funcionarios = $this->_dados_requisicao->funcionarios;
                        $dados_servico["dados_servico_funcionarios"] = $this->_model_servico->addFuncionariosServico($this->_array_funcionarios, $this->_id_servico);

                        $this->_array_genero = $this->_dados_requisicao->generos;
                        $dados_servico["dados_servico_genero"] = $this->_model_servico->addGeneroServico($this->_array_genero, $this->_id_servico);

                        $this->_array_tipos_atendimento = $this->_dados_requisicao->tipos_atendimento;
                        $dados_servico["dados_servico_tipo_atendimento"] = $this->_model_servico->addTipoAtendimentoServico($this->_array_tipos_atendimento, $this->_id_servico);

                        return $dados_servico;
                    }
                } elseif ($this->_flag == "updateServico") {

                    if (isset($_POST["envio_form"])) {

                        $envio_form = $_POST["envio_form"];

                        if ($envio_form == "true") {

                            if (isset($_FILES["imagem_servico"])) {
                                if ($_FILES["imagem_servico"]["error"] == 4) {
                                    return ("chegou a req sem imagem de serviço");
                                } else {
                                    return $this->_model_servico->updateServico($this->_id_servico);
                                }
                            }
                        }
                    } else {

                        $dados_servico["dados_servico"] = $this->_model_servico->updateServico($this->_id_servico);

                        $this->_model_servico->removerFuncionarios();
                        $this->_array_funcionarios = $this->_dados_requisicao->funcionarios;
                        $dados_servico["dados_servico_funcionarios"] = $this->_model_servico->addFuncionariosServico($this->_array_funcionarios, $this->_id_servico);

                        $dados_servico["dados_servico_especialidadePartesCorpo"] = $this->_model_servico->updateEspecialidadePartesCorpo();

                        $this->_model_servico->limparGeneros();
                        $this->_array_genero = $this->_dados_requisicao->generos;
                        $dados_servico["dados_servico_genero"] = $this->_model_servico->addGeneroServico($this->_array_genero, $this->_id_servico);

                        $this->_model_servico->limparTipoAtendimento();
                        $this->_array_tipos_atendimento = $this->_dados_requisicao->tipos_atendimento;
                        $dados_servico["dados_servico_tipo_atendimento"] = $this->_model_servico->addTipoAtendimentoServico($this->_array_tipos_atendimento, $this->_id_servico);

                        return $dados_servico;
                    }
                }

                break;

            case 'DELETE':

                if ($this->_flag == "desabilitarServico") {

                    return $this->_model_servico->desabilitarServico();
                }

                break;

            default:
                # code...
                break;
        }
    }
}