if (!localStorage.getItem("cookies_aceitos")) {
    const banner = document.createElement("div");

    banner.innerHTML = `
        <div style="
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #222;
            color: white;
            padding: 15px;
            text-align: center;
            z-index: 9999;
        ">
            Este site utiliza cookies para melhorar sua experiência.
            <button id="aceitarCookies">Aceitar</button>
        </div>
    `;

    document.body.appendChild(banner);

    document.getElementById("aceitarCookies").onclick = () => {
        localStorage.setItem("cookies_aceitos", "true");
        banner.remove();
    };
}
