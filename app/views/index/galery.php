<?php if (!empty($galeriaHome)): ?>
<section class="user-gallery-static" style="margin: 50px 0; padding: 0 20px;">
    <div class="section-head" style="text-align:center; margin-bottom: 30px;">
        <h2 style="color:var(--text); font-size: 1.8rem;">Comunidad Teatral</h2>
        <p style="color:var(--muted);">Fotos aleatorias compartidas por nuestros usuarios</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; max-width: var(--max); margin: 0 auto;">
        <?php foreach ($galeriaHome as $img): ?>
            <div class="img-card glass" style="overflow:hidden; border-radius:12px; border: 1px solid rgba(255,255,255,0.08); position: relative;">
                
                <img src="<?= h(BASE_URL . $img['RutaImagen']) ?>" 
                     alt="Teatro <?= h($img['Sala']) ?>" 
                     loading="lazy" 
                     style="width:100%; height:200px; object-fit:cover; display:block; transition: opacity 0.3s;">
                
                <div style="padding:12px; background: rgba(20, 20, 30, 0.85); border-top: 1px solid rgba(255,255,255,0.05);">
                    <div style="font-weight: 600; color: var(--accent); font-size: 0.85rem; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">
                        <i class="fas fa-landmark" style="margin-right:5px;"></i><?= h($img['Sala']) ?>
                    </div>
                    <div style="font-size: 0.75rem; color: #999; margin-top: 4px;">
                        Subida por: <span style="color:#ddd;"><?= h($img['NombreUsuario']) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>