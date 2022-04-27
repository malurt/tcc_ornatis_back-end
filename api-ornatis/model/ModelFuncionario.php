<?php

class ModelFuncionario
{

    private $_conexao;
    private $_method;

    private $_id_empresa;

    //ATRIBUTOS DE FUNCIONÁRIO
    private $_id_funcionario;

    private $_dados_funcionario;

    private $_nome_funcionario;
    private $_foto_perfil;

    //ATRIBUTOS DE LOGIN
    private $_cod_funcionario;
    private $_senha;

    //ATRIBUTOS DE DIA DE TRABALHO
    private $_hora_inicio;
    private $_hora_termino;
    private $_id_dia_semana;

    public function __construct($conexao)
    {

        $this->_method = $_SERVER['REQUEST_METHOD'];

        $json = file_get_contents("php://input");
        $this->_dados_funcionario  = json_decode($json);

        switch ($this->_method) {
            case 'POST':

                $this->_id_funcionario = $_POST["id_funcionario"] ?? $this->_dados_funcionario->id_funcionario ?? null;
                $this->_nome_funcionario = $_POST["nome_funcionario"] ?? $this->_dados_funcionario->nome_funcionario;
                $this->_foto_perfil = $_FILES["foto_perfil"] ?? null;

                $this->_id_empresa = $_POST["id_empresa"] ?? $this->_dados_funcionario->id_empresa;

                //login
                // $this->_cod_funcionario = $_POST["cod_funcionario"] ?? $this->_dados_funcionario->cod_funcionario;
                $this->_senha = $_POST["senha"] ?? $this->_dados_funcionario->senha;

                $this->_hora_inicio = $_POST["hora_inicio"] ?? $this->_dados_funcionario->hora_inicio ?? null;
                $this->_hora_termino = $_POST["hora_termino"] ?? $this->_dados_funcionario->hora_termino ?? null;
                $this->_id_dia_semana = $_POST["id_dia_semana"] ?? $this->_dados_funcionario->id_dia_semana ?? null;

                break;

            default:

                $this->_id_funcionario = $_GET["id_funcionario"] ?? $this->_dados_funcionario->id_funcionario;
                $this->_id_empresa = $_GET["id_empresa"] ?? $this->_dados_funcionario->id_empresa;

                break;
        }

        $this->_conexao = $conexao;
    }

    public function getFuncionariosEmpresa()
    {

        $sql = "SELECT * from tbl_funcionario WHERE id_empresa = ?";

        $stm = $this->_conexao->prepare($sql);
        $stm->bindValue(1, $this->_id_empresa);

        $stm->execute();
        return $stm->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getInformacoesFuncionario()
    {
        $sql = "SELECT tbl_funcionario.nome_funcionario, 
        tbl_funcionario.foto_perfil 
        FROM tbl_funcionario
        WHERE id_funcionario = ?";

        $stm = $this->_conexao->prepare($sql);
        $stm->bindValue(1, $this->_id_funcionario);

        $stm->execute();
        return $stm->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getDiaTrabalho()
    {

        $sql = "SELECT tbl_dia_trabalho.hora_inicio, 
		tbl_dia_trabalho.hora_termino, 
		tbl_dia_semana.dia_da_semana,
		tbl_dia_semana.id_dia_semana
        
		FROM tbl_dia_trabalho
			inner join tbl_dia_semana 
            on tbl_dia_trabalho.id_dia_semana = tbl_dia_semana.id_dia_semana
            
		WHERE id_funcionario = ?";


        $stm = $this->_conexao->prepare($sql);
        $stm->bindValue(1, $this->_id_funcionario);

        $stm->execute();
        return $stm->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function createFuncionario()
    {

        if (isset($_POST["envio_form"])) {

            $envio_form = $_POST["envio_form"];

            if ($envio_form == "true") {
                if ($_FILES["foto_perfil"]["error"] == 4) {
                    //não faz nada pq não veio img
                    return "estariamos fazendo nada porque não veio img";
                } else {

                    $nomeArquivo = $_FILES["foto_perfil"]["name"];

                    $extensao = pathinfo($nomeArquivo, PATHINFO_EXTENSION);
                    $novoNomeArquivo = md5(microtime()) . ".$extensao";

                    move_uploaded_file($_FILES["foto_perfil"]["tmp_name"], "../../../upload/imagem_perfil_salao/$novoNomeArquivo");

                    $sql = "INSERT INTO tbl_funcionario (foto_perfil) 
                    VALUES (?) 
                    WHERE id_funcionario = ?";

                    $stm = $this->_conexao->prepare($sql);

                    $stm->bindvalue(1, $novoNomeArquivo);
                    $stm->bindvalue(2, $this->_id_funcionario);

                    $stm->execute();
                }
            }
        } else {

            $sql = "INSERT INTO tbl_funcionario (nome_funcionario, id_empresa)
            VALUES (?, ?)";

            $stm = $this->_conexao->prepare($sql);
            $stm->bindValue(1, $this->_nome_funcionario);
            $stm->bindValue(2, $this->_id_empresa);
            $stm->execute();

            $idFuncionario = $this->_conexao->lastInsertId();

            $primeiroNomeFuncionario = strtok($this->_nome_funcionario, " ");
            $codigo = substr(uniqid(rand()), 0, 4);

            $this->_cod_funcionario = $primeiroNomeFuncionario . $codigo;

            $sql = "INSERT INTO tbl_login_funcionario (cod_funcionario, senha, id_funcionario)
            VALUES (?, ?, ?)";

            $stm = $this->_conexao->prepare($sql);
            $stm->bindValue(1, $this->_cod_funcionario);
            $stm->bindValue(2, $this->_senha);
            $stm->bindValue(3, $idFuncionario);
            $stm->execute();

            return $idFuncionario;
        }
    }

    public function createDiaTrabalhoFuncionario($diasTrabalho, $idFuncionarioRecebido)
    {

        foreach ($diasTrabalho as $diaTrabalho) {

            $this->_hora_inicio = $diaTrabalho->hora_inicio;
            $this->_hora_termino = $diaTrabalho->hora_termino;
            $this->_id_dia_semana = $diaTrabalho->id_dia_semana;
            $this->_id_funcionario = $idFuncionarioRecebido;

            $sql = "INSERT INTO tbl_dia_trabalho (hora_inicio, hora_termino, 
            id_dia_semana, id_funcionario)
            VALUES (?, ?, ?, ?)";

            $stm = $this->_conexao->prepare($sql);
            $stm->bindValue(1, $this->_hora_inicio);
            $stm->bindValue(2, $this->_hora_termino);
            $stm->bindValue(3, $this->_id_dia_semana);
            $stm->bindValue(4, $this->_id_funcionario);

            $stm->execute();
        }

        return "Success";
    }

    //DELETE

    public function desabilitarFuncionario($idFuncionarioRecebido)
    {

        $this->_id_funcionario = $idFuncionarioRecebido;

        $sql = "UPDATE tbl_funcionario SET
        habilitado = 0
        WHERE id_funcionario = ?";

        $stm = $this->_conexao->prepare($sql);
        $stm->bindValue(1, $this->_id_funcionario);

        if ($stm->execute()) {
            return "success";
        }
    }

    public function limparDiasTrabalho()
    {
        $sql = "DELETE FROM tbl_dia_trabalho WHERE id_funcionario = ?";

        $stm = $this->_conexao->prepare($sql);
        $stm->bindValue(1, $this->_id_funcionario);

        $stm->execute();
    }

    //UPDATE

    public function updateFuncionarioAdm()
    {
        if (isset($_POST["envio_form"])) {

            $envio_form = $_POST["envio_form"];

            if ($envio_form == "true") {

                if ($_FILES["foto_perfil"]["error"] == 4) {
                    //não faz nada pq não veio img
                    return "estariamos fazendo nada porque não veio img";
                } else {
                    //selecionar imagem de perfil 
                    $sqlImg = "SELECT foto_perfil FROM tbl_funcionario WHERE id_funcionario = ?";

                    $stm = $this->_conexao->prepare($sqlImg);
                    $stm->bindValue(1, $this->_id_funcionario);

                    $stm->execute();

                    $funcionario = $stm->fetchAll(\PDO::FETCH_ASSOC);

                    //exclusão da imagem antiga se tiver
                    if ($funcionario[0]["foto_perfil"] != null) {
                        unlink("../../../upload/foto_perfil_funcionario/" . $funcionario[0]["foto_perfil"]);
                    }

                    //nova imagem
                    $nomeArquivo = $_FILES["foto_perfil"]["name"];

                    $extensao = pathinfo($nomeArquivo, PATHINFO_EXTENSION);
                    $novoNomeArquivo = md5(microtime()) . ".$extensao";


                    move_uploaded_file($_FILES["foto_perfil"]["tmp_name"], "../../../upload/foto_perfil_funcionario/$novoNomeArquivo");

                    $sql = "UPDATE tbl_funcionario SET
                    foto_perfil = ? WHERE id_funcionario = ? ";

                    $stm = $this->_conexao->prepare($sql);

                    $stm->bindvalue(1, $novoNomeArquivo);
                    $stm->bindvalue(2, $this->_id_funcionario);

                    $stm->execute();
                }
            }
        } else {

            $sql = "UPDATE tbl_funcionario SET 
            nome_funcionario = ?
            WHERE id_funcionario = ?";

            $stm = $this->_conexao->prepare($sql);
            $stm->bindValue(1, $this->_nome_funcionario);
            $stm->bindValue(2, $this->_id_funcionario);
            $stm->execute();
        }
    }

    public function updateLoginFuncionario()
    {

        $sql = "UPDATE tbl_login_funcionario SET
        cod_funcionario = ?,
        senha = ?
        WHERE id_funcionario = ?";

        $stm = $this->_conexao->prepare($sql);
        $stm->bindValue(1, $this->_cod_funcionario);
        $stm->bindValue(2, $this->_senha);
        $stm->bindValue(3, $this->_id_funcionario);
        $stm->execute();

        return "Dados atualizados com sucesso!";
    }
}
