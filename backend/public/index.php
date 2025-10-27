<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Licita.pub - Plataforma de Licitações Públicas do Brasil. Encontre oportunidades de negócios com o governo de forma simples e transparente.">
    <meta name="keywords" content="licitações, PNCP, licitações públicas, compras governamentais, pregão, licitação brasil">
    <meta name="author" content="Licita.pub">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://licita.pub/">
    <meta property="og:title" content="Licita.pub - Plataforma de Licitações Públicas">
    <meta property="og:description" content="Conectando fornecedores a oportunidades de negócios com o governo brasileiro.">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://licita.pub/">
    <meta property="twitter:title" content="Licita.pub - Plataforma de Licitações Públicas">
    <meta property="twitter:description" content="Conectando fornecedores a oportunidades de negócios com o governo brasileiro.">

    <title>Licita.pub - Licitações Públicas do Brasil</title>

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='0.9em' font-size='90'>📋</text></svg>">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .pulse-dot {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-2">
                    <span class="text-3xl">📋</span>
                    <h1 class="text-2xl font-bold text-gray-900">licita<span class="text-blue-600">.pub</span></h1>
                </div>
                <div class="hidden md:flex space-x-6">
                    <a href="#sobre" class="text-gray-600 hover:text-blue-600 transition">Sobre</a>
                    <a href="#planos" class="text-gray-600 hover:text-blue-600 transition">Planos</a>
                    <a href="#funcionalidades" class="text-gray-600 hover:text-blue-600 transition">Funcionalidades</a>
                    <a href="#contato" class="text-gray-600 hover:text-blue-600 transition">Contato</a>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="flex items-center space-x-1 text-sm text-green-600">
                        <span class="pulse-dot inline-block w-2 h-2 bg-green-500 rounded-full"></span>
                        <span class="hidden sm:inline">Em Desenvolvimento</span>
                    </span>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="gradient-bg text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-5xl md:text-6xl font-bold mb-6">
                Licitações Públicas<br>do Brasil
            </h2>
            <p class="text-xl md:text-2xl mb-8 text-gray-100 max-w-3xl mx-auto">
                Conectando fornecedores a oportunidades de negócios com o governo brasileiro de forma simples, transparente e eficiente.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/frontend/cadastro.html" class="bg-white text-blue-600 px-8 py-4 rounded-lg font-semibold hover:bg-gray-100 transition shadow-lg">
                    Começar Grátis - 10 consultas/dia
                </a>
                <a href="/frontend/login.html" class="bg-transparent border-2 border-white text-white px-8 py-4 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition">
                    Já tenho conta
                </a>
            </div>
            <p class="mt-6 text-sm text-gray-200">
                ✅ Sem cartão de crédito | ✅ 10 consultas gratuitas por dia | ✅ Cadastro em 1 minuto
            </p>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                <div class="p-6">
                    <div class="text-4xl font-bold text-blue-600 mb-2">5.000+</div>
                    <div class="text-gray-600">Órgãos Públicos</div>
                </div>
                <div class="p-6">
                    <div class="text-4xl font-bold text-blue-600 mb-2">27</div>
                    <div class="text-gray-600">Estados</div>
                </div>
                <div class="p-6">
                    <div class="text-4xl font-bold text-blue-600 mb-2">100%</div>
                    <div class="text-gray-600">Integrado com PNCP</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sobre Section -->
    <section id="sobre" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Sobre o Licita.pub</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Uma plataforma moderna para facilitar o acesso a licitações públicas de todo o Brasil
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Nossa Missão</h3>
                    <p class="text-gray-600 mb-6 leading-relaxed">
                        Democratizar o acesso às licitações públicas brasileiras, conectando fornecedores de todos os portes a oportunidades de negócios com órgãos governamentais de forma transparente e eficiente.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Base Legal</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Desenvolvido em conformidade com a <strong>Lei 14.133/2021</strong> (Nova Lei de Licitações), nossa plataforma integra-se diretamente com o Portal Nacional de Contratações Públicas (PNCP), garantindo acesso a informações oficiais e atualizadas.
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h4 class="font-semibold text-gray-900 mb-2">🎯 Foco no Usuário</h4>
                        <p class="text-gray-600 text-sm">Interface simples e intuitiva para facilitar a busca de licitações</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h4 class="font-semibold text-gray-900 mb-2">🔔 Alertas Inteligentes</h4>
                        <p class="text-gray-600 text-sm">Receba notificações de licitações que combinam com seu perfil</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h4 class="font-semibold text-gray-900 mb-2">📊 Dados Atualizados</h4>
                        <p class="text-gray-600 text-sm">Sincronização automática com a base de dados do PNCP</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Planos Section -->
    <section id="planos" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Planos e Preços</h2>
                <p class="text-xl text-gray-600">Escolha o plano ideal para o seu negócio</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Plano Anônimo -->
                <div class="bg-gray-50 p-8 rounded-xl border-2 border-gray-200">
                    <div class="text-center mb-6">
                        <div class="text-4xl mb-4">🔓</div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Anônimo</h3>
                        <div class="text-3xl font-bold text-gray-900 mb-2">Grátis</div>
                        <p class="text-gray-600">Sem cadastro</p>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700"><strong>5 consultas detalhadas</strong> por dia</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">Listagem completa de licitações</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">Busca simples por UF e município</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-gray-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-400">Sem favoritos</span>
                        </li>
                    </ul>
                    <a href="/api/licitacoes/listar.php" class="block w-full text-center bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-300 transition">
                        Explorar Agora
                    </a>
                </div>

                <!-- Plano FREE -->
                <div class="bg-blue-50 p-8 rounded-xl border-2 border-blue-500 relative">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-blue-500 text-white px-4 py-1 rounded-full text-sm font-semibold">
                        Mais Popular
                    </div>
                    <div class="text-center mb-6">
                        <div class="text-4xl mb-4">⭐</div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">FREE</h3>
                        <div class="text-3xl font-bold text-gray-900 mb-2">Grátis</div>
                        <p class="text-gray-600">Com cadastro</p>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700"><strong>10 consultas detalhadas</strong> por dia</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">Tudo do plano Anônimo +</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">Filtros avançados</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">Salvar favoritos (em breve)</span>
                        </li>
                    </ul>
                    <a href="/frontend/cadastro.html" class="block w-full text-center bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                        Cadastrar Grátis
                    </a>
                </div>

                <!-- Plano PREMIUM -->
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 p-8 rounded-xl border-2 border-purple-300">
                    <div class="text-center mb-6">
                        <div class="text-4xl mb-4">👑</div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">PREMIUM</h3>
                        <div class="text-3xl font-bold text-gray-900 mb-2">Em breve</div>
                        <p class="text-gray-600">Recursos ilimitados</p>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700"><strong>Consultas ilimitadas</strong></span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">Alertas personalizados por email</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">Exportação Excel/PDF</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">API de integração</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">Suporte prioritário</span>
                        </li>
                    </ul>
                    <button disabled class="block w-full text-center bg-gray-300 text-gray-500 px-6 py-3 rounded-lg font-semibold cursor-not-allowed">
                        Em Breve
                    </button>
                </div>
            </div>

            <div class="mt-12 text-center">
                <p class="text-gray-600">
                    💡 <strong>Dica:</strong> Todos os planos têm reset automático após 24 horas da primeira consulta do dia
                </p>
            </div>
        </div>
    </section>

    <!-- Funcionalidades Section -->
    <section id="funcionalidades" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Funcionalidades</h2>
                <p class="text-xl text-gray-600">Ferramentas poderosas para facilitar seu trabalho</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-gray-50 p-8 rounded-xl card-hover">
                    <div class="text-4xl mb-4">🔍</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Busca Avançada</h3>
                    <p class="text-gray-600">
                        Filtros por UF, município, modalidade, valor e palavra-chave para encontrar exatamente o que você precisa.
                    </p>
                </div>

                <div class="bg-gray-50 p-8 rounded-xl card-hover">
                    <div class="text-4xl mb-4">⭐</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Favoritos</h3>
                    <p class="text-gray-600">
                        Salve e organize suas licitações de interesse para acompanhar prazos e atualizações.
                    </p>
                </div>

                <div class="bg-gray-50 p-8 rounded-xl card-hover">
                    <div class="text-4xl mb-4">📈</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Estatísticas</h3>
                    <p class="text-gray-600">
                        Visualize dados agregados sobre licitações por estado, modalidade e valores.
                    </p>
                </div>

                <div class="bg-gray-50 p-8 rounded-xl card-hover">
                    <div class="text-4xl mb-4">🔔</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Alertas Personalizados</h3>
                    <p class="text-gray-600">
                        Configure alertas com seus critérios e receba notificações de novas licitações.
                    </p>
                </div>

                <div class="bg-gray-50 p-8 rounded-xl card-hover">
                    <div class="text-4xl mb-4">🔗</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Integração PNCP</h3>
                    <p class="text-gray-600">
                        Acesso direto aos editais e documentos oficiais publicados no portal do governo.
                    </p>
                </div>

                <div class="bg-gray-50 p-8 rounded-xl card-hover">
                    <div class="text-4xl mb-4">📱</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Responsivo</h3>
                    <p class="text-gray-600">
                        Acesse de qualquer dispositivo: computador, tablet ou smartphone.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Status Section -->
    <section class="py-20 bg-blue-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="bg-white p-8 rounded-xl shadow-md">
                <div class="text-5xl mb-4">🚀</div>
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Sistema em Fase Beta</h2>
                <p class="text-lg text-gray-600 mb-6">
                    Backend implementado com sucesso! Estamos finalizando o frontend para oferecer
                    a melhor experiência em licitações públicas do Brasil.
                </p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="flex flex-col items-center justify-center space-y-2 text-green-600">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm font-semibold">Backend Completo</span>
                    </div>
                    <div class="flex flex-col items-center justify-center space-y-2 text-green-600">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm font-semibold">API Funcionando</span>
                    </div>
                    <div class="flex flex-col items-center justify-center space-y-2 text-green-600">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm font-semibold">Sistema Freemium</span>
                    </div>
                    <div class="flex flex-col items-center justify-center space-y-2 text-green-600">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm font-semibold">Integração PNCP</span>
                    </div>
                </div>
                <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="/frontend/cadastro.html" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                        Criar Conta Grátis
                    </a>
                    <a href="#planos" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-200 transition">
                        Ver Planos
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Contato Section -->
    <section id="contato" class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">Entre em Contato</h2>
            <p class="text-xl text-gray-600 mb-8">
                Tem dúvidas ou sugestões? Estamos aqui para ajudar!
            </p>
            <div class="bg-gray-50 p-8 rounded-xl">
                <div class="flex flex-col sm:flex-row gap-6 justify-center items-center">
                    <a href="mailto:contato@licita.pub" class="flex items-center space-x-2 text-blue-600 hover:text-blue-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span class="font-semibold">contato@licita.pub</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-8 mb-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <span class="text-2xl">📋</span>
                        <h3 class="text-white font-bold text-xl">licita<span class="text-blue-400">.pub</span></h3>
                    </div>
                    <p class="text-sm">
                        Plataforma de licitações públicas do Brasil
                    </p>
                </div>

                <div>
                    <h4 class="text-white font-semibold mb-4">Links Importantes</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="https://pncp.gov.br" target="_blank" rel="noopener" class="hover:text-white transition">Portal PNCP</a></li>
                        <li><a href="https://www.gov.br/compras" target="_blank" rel="noopener" class="hover:text-white transition">Compras.gov.br</a></li>
                        <li><a href="https://www.planalto.gov.br/ccivil_03/_ato2019-2022/2021/lei/l14133.htm" target="_blank" rel="noopener" class="hover:text-white transition">Lei 14.133/2021</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-semibold mb-4">Informações</h4>
                    <ul class="space-y-2 text-sm">
                        <li>Status: Fase Beta</li>
                        <li>Versão: 2.0.0 (Freemium)</li>
                        <li>Backend: ✅ Completo</li>
                        <li>Última atualização: <?php echo date('d/m/Y'); ?></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 text-center text-sm">
                <p>&copy; <?php echo date('Y'); ?> Licita.pub - Todos os direitos reservados</p>
                <p class="mt-2">Desenvolvido com ❤️ para facilitar o acesso a licitações públicas no Brasil</p>
            </div>
        </div>
    </footer>

    <!-- Scroll suave -->
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>

</body>
</html>
