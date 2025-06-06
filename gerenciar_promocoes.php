<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || $_SESSION['perfil'] !== 'admin') {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

// listar produtos para formulário
$sql_produtos = "SELECT id, nome_produtos FROM produtos";
$resultado_produtos = $conexao->query($sql_produtos);
$produtos = $resultado_produtos->fetch_all(MYSQLI_ASSOC);

// cadastrar ou atualizar promoção
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $tipo = $_POST['tipo'];
    $valor = $_POST['valor'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $produto_id = $_POST['$produto_id'];
    $id = !empty($_POST['id']) ? $_POST['id'] : null;

    // validar datas
    if (empty($data_inicio) || empty($data_fim)) {
        header("Location: gerenciar_promocoes.php?erro=data_vazia");
        exit();
    }
    if (strtotime($data_inicio) === false || strtotime($data_fim) === false) {
        header("Location: gerenciar_promocoes.php?erro=data_invalida");
        exit();
    }
    if (strtotime($data_inicio) > strtotime($data_fim)) {
        header("Location: gerenciar_promocoes.php?erro=data_inicio_maior");
        exit();
    }
    // validar produto
    if (empty($produto_id)) {
        header("Location: gerenciar_promocoes.php?erro=produto_nao_selecionado");
        exit();
    }
    if ($id) {
        // atualizar promoção existente
        $sql = "UPDATE promocoes SET nome = ?, tipo = ?, valor = ?, data_inicio = ?, data_fim = ?, produto_id = ? WHERE id = ?";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("ssdssii", $nome, $tipo, $valor, $data_inicio, $data_fim, $produto_id, $id);
    } else {
        // cadastrar nova promoção
        $sql = "INSERT INTO promocoes (nome, tipo, valor, data_inicio, data_fim, produto_id) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("ssdssi", $nome, $tipo, $valor, $data_inicio, $data_fim, $produto_id);
    }
    if ($stmt->execute()) {
        header("Location: gerenciar_promocoes.php?sucesso=1");
    } else {
        header("Location: gerenciar_promocoes.php?erro=sql");
    }
    $stmt->close();
    exit();
}

// excluir promoção
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $sql = "DELETE FROM promocoes WHERE id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: gerenciar_promocoes.php");
    exit();
}

// listar promoções
$sql_promocoes = "SELECT * FROM promocoes";
$resultado_promocoes = $conexao->query($sql_promocoes);
$promocoes = [];
while ($row = $resultado_promocoes->fetch_assoc()) {
    $promocoes[] = $row;
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Promoções - Panificadora</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Gestão de Estoque - Panificadora</h1>
        <nav>
            <a href="controle_estoque.php">Dashboard</a>
            <?php if (in_array($_SESSION['perfil'], ['admin', 'gerente'])): ?>
                <a href="adicionar_produto.php">Adicionar Produto</a>
                <a href="planejamento_producao.php">Planejamento de Produção</a>
            <?php endif; ?>
            <a href="registrar_venda.php">Registrar Venda</a>
            <a href="listar_produtos.php">Listar Produtos</a>
            <?php if (in_array($_SESSION['perfil'], ['admin', 'gerente'])): ?>
                <a href="relatorios.php">Relatórios</a>
                <a href="receitas.php">Receitas</a>
                <a href="desperdicio.php">Desperdício</a>
                <a href="gerenciar_promocoes.php">Gerenciar Promoções</a>
                <a href="gerenciar_fornecedores.php">Gerenciar Fornecedores</a>
                <a href="exportar_dados.php">Exportar Dados</a>
            <?php endif; ?>
            <?php if ($_SESSION['perfil'] === 'admin'): ?>
                <a href="gerenciar_usuarios.php">Gerenciar Usuários</a>
                <a href="ver_logs.php">Ver Logs</a>
                <a href="gerenciar_backups.php">Gerenciar Backups</a>
            <?php endif; ?>
            <a href="logout.php">Sair</a>
        </nav>
    </header>
    <div class="container">
        <h2>Gerenciar Promoções</h2>
        <?php if (isset($_GET['sucesso'])): ?>
            <p style="color: green;">Promoção salva com sucesso!</p>
        <?php endif; ?>
        <?php if (isset($_GET['erro'])): ?>
            <p style="color: red;">
                <?php
                if ($_GET['erro'] === 'data_vazia') echo "Erro: As datas de início e fim são obrigatórias.";
                elseif ($_GET['erro'] === 'data_invalida') echo "Erro: Data inválida.";
                elseif ($_GET['erro'] === 'data_inicio_maior') echo "Erro: A data de início não pode ser maior que a data de fim.";
                elseif ($_GET['erro'] === 'produto_nao_selecionado') echo "Erro: Selecione um produto para a promoção.";
                else echo "Erro ao salvar a promoção.";
                ?>
            </p>
        <?php endif; ?>

        <!-- formulário para cadastrar/editar promoção -->
        <form method="POST" action="gerenciar_promocoes.php">
            <input type="hidden" name="id" value="">
            <label for="nome">Nome da Promoção:</label>
            <input type="text" name="nome" required><br>
            
            <label for="produto_id">Produto:</label>
            <select name="produto_id" required>
                <option value="">Selecione um produto</option>
                <?php foreach ($produtos as $produto): ?>
                    <option value="<?php echo $produto['id']; ?>">
                        <?php echo htmlspecialchars($produto['nome_produto']); ?>
                    </option>
                <?php endforeach; ?>
            </select><br>

            <label for="tipo">Tipo de Promoção:</label>
            <select name="tipo" required>
                <option value="percentual">Desconto Percentual</option>
                <option value="leve_pague">Leve X, Pague Y</option>
            </select><br>

            <label for="valor">Valor:</label>
            <input type="number" step="0.01" name="valor" required placeholder="Ex.: 10 para 10% ou 2 para Leve 3, Pague 2"><br>

            <label for="data_inicio">Data Início:</label>
            <input type="date" name="data_inicio" required><br>

            <label for="data_fim">Data Fim:</label>
            <input type="date" name="data_fim" required><br>

            <button type="submit">Salvar Promoção</button>
        </form>

        <!-- listar promoções -->
        <h3>Promoções Cadastradas</h3>
        <table border="1">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Produto</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Data Início</th>
                    <th>Data Fim</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($promocoes)): ?>
                    <?php foreach ($promocoes as $promocao): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($promocao['nome']); ?></td>
                            <td><?php echo htmlspecialchars($promocao['nome_produto']); ?></td>
                            <td><?php echo $promocao['tipo'] === 'percentual' ? 'Desconto Percentual' : 'Leve X, Pague Y'; ?></td>
                            <td><?php echo htmlspecialchars($promocao['valor']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($promocao['data_inicio'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($promocao['data_fim'])); ?></td>
                            <td>
                                <a href="editar_promocao.php?id=<?php echo $promocao['id']; ?>">Editar</a> |
                                <a href="gerenciar_promocoes.php?excluir=<?php echo $promocao['id']; ?>" 
                                   onclick="return confirm('Tem certeza que deseja excluir esta promoção?')">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">Nenhuma promoção cadastrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>