// Configuration API
const baseURL = "http://localhost/omk_thyp_25-26_clone/api/items";
const key_identity = "dURgA6sfAT3VGheTS97xwZj983Cr33fv";
const key_credential = "Z7QCZ2AM0G0wwNAwVuSGwZ4tbUYCr6gb";


// ########################################
// Lecture des items Omeka S
// ########################################

document.getElementById("btnGet").addEventListener("click", () => {
    const url = `${baseURL}?key_identity=${key_identity}&key_credential=${key_credential}`;
    fetch(url)
    .then(response => {
    if (!response.ok) throw new Error(`Erreur HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        let chaine = "";
        data.forEach(item => {
            const titre = item["dcterms:title"]?.[0]?.["@value"] || "(sans titre)";
            const texte = item["dcterms:description"]?.[0]?.["@value"] || "(sans texte)";
            const resume = item["dcterms:abstract"]?.[0]?.["@value"] || "(sans résumé)";
            chaine += `
                <tr>
                    <td>${titre}</td>
                    <td>${texte}</td>
                    <td>${resume}</td>
                </tr>
            `;
        });
        document.querySelector("#resultat tbody").innerHTML = chaine;
    })
    .catch(err => {
        document.getElementById("resultat").textContent =
            "Erreur de lecture : " + err.message;
    });
});


// ########################################
// Ajout d’un item dans Omeka S
// ########################################

document.getElementById("btnEnvoyer").addEventListener("click", () => {
    const titre = document.getElementById("titre").value.trim();
    const texte = document.getElementById("texte").value.trim();

    // Génération d’un résumé automatique (~50 mots)
    const mots = texte.split(/\s+/);
    const resume = mots.slice(0, 50).join(" ");

    const newItem = {
        "o:is_public": true,

        // Titre obligatoire
        "dcterms:title": [
            {
                "type": "literal",
                "property_id": "auto",
                "@value": titre
            }
        ],

        // Texte
        "dcterms:description": [
            {
                "type": "literal",
                "property_id": "auto",
                "@value": texte
            }
        ],

        // Résumé
        "dcterms:abstract": [
            { 
                "type": "literal",
                "property_id": "auto",
                "@value": resume
            }
        ]
    };

    fetch(`${baseURL}?key_identity=${key_identity}&key_credential=${key_credential}`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(newItem)
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`Erreur HTTP ${response.status}: ${text}`);
            });
        }
        return response.json();
    })
    .then(data => {
        document.getElementById("responseItem").textContent =
            "Item créé avec succès :\n" + JSON.stringify(data, null, 2);
    })
    .catch(err => {
        document.getElementById("responseItem").textContent =
            "Erreur : " + err.message;
    });
});