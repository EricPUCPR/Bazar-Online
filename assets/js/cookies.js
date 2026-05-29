if (!localStorage.getItem("cookies_aceitos")) {

    const overlay = document.createElement("div");

    overlay.innerHTML = `
        <div id="cookieOverlay" style="
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.35);
            backdrop-filter: blur(2px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            padding: 20px;
        ">

            <div style="
                background: white;
                width: 100%;
                max-width: 650px;
                border-radius: 18px;
                padding: 30px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.18);
                font-family: Arial, sans-serif;
            ">

                <h2 style="
                    margin-top: 0;
                    color: #0d5c91;
                    font-size: 32px;
                ">
                    Preferências de privacidade
                </h2>

                <p style="
                    color: #555;
                    font-size: 18px;
                    line-height: 1.5;
                ">
                    Usamos cookies necessários para manter sua sessão,
                    segurança e consentimento.
                    Cookies opcionais só ficam ativos com sua escolha.
                </p>

                <div style="
                    background: #f1f4f8;
                    border-radius: 14px;
                    padding: 20px;
                    margin-top: 20px;
                    margin-bottom: 25px;
                ">

                    <label style="display:block; margin-bottom:15px;">
                        <input type="checkbox" checked disabled>
                        Necessários para login, segurança e funcionamento
                    </label>

                    <label style="display:block; margin-bottom:15px;">
                        <input type="checkbox" id="cookiesInterface">
                        Preferências da interface
                    </label>

                    <label style="display:block;">
                        <input type="checkbox" id="cookiesStats">
                        Estatísticas de uso anonimizadas
                    </label>

                </div>

                <div style="
                    display: flex;
                    gap: 12px;
                    flex-wrap: wrap;
                ">

                    <button id="recusarCookies" style="
                        padding: 14px 20px;
                        border: none;
                        border-radius: 10px;
                        background: #e9edf2;
                        cursor: pointer;
                        font-weight: bold;
                    ">
                        Recusar opcionais
                    </button>

                    <button id="salvarCookies" style="
                        padding: 14px 20px;
                        border: none;
                        border-radius: 10px;
                        background: #0d5c91;
                        color: white;
                        cursor: pointer;
                        font-weight: bold;
                    ">
                        Salvar escolha
                    </button>

                    <button id="aceitarTodosCookies" style="
                        padding: 14px 20px;
                        border: none;
                        border-radius: 10px;
                        background: #0d5c91;
                        color: white;
                        cursor: pointer;
                        font-weight: bold;
                    ">
                        Aceitar todos
                    </button>

                </div>

            </div>
        </div>
    `;

    document.body.appendChild(overlay);

    function fecharBanner() {
        overlay.remove();
    }

    document.getElementById("aceitarTodosCookies").onclick = () => {

        localStorage.setItem("cookies_aceitos", "true");

        localStorage.setItem("cookies_interface", "true");
        localStorage.setItem("cookies_stats", "true");

        fecharBanner();
    };

    document.getElementById("recusarCookies").onclick = () => {

        localStorage.setItem("cookies_aceitos", "true");

        localStorage.setItem("cookies_interface", "false");
        localStorage.setItem("cookies_stats", "false");

        fecharBanner();
    };

    document.getElementById("salvarCookies").onclick = () => {

        localStorage.setItem("cookies_aceitos", "true");

        localStorage.setItem(
            "cookies_interface",
            document.getElementById("cookiesInterface").checked
        );

        localStorage.setItem(
            "cookies_stats",
            document.getElementById("cookiesStats").checked
        );

        fecharBanner();
    };
}