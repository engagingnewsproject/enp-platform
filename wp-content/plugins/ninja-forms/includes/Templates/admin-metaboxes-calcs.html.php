<ul>
    <?php foreach( $data as $name => $contents ):?>
        <li>
            <strong><?php echo( $name ); ?></strong>
            <?php
                echo( ' = ' . esc_html( $contents[ 'value' ] ) );
                if( isset( $_GET[ 'calcs_debug' ] ) ) {
                    echo( '<br />RAW: ' . esc_html( $contents[ 'raw' ] ));
                    echo( '<br />PARSED: ' . esc_html( $contents[ 'parsed' ] ) );
                }
            ?>
        </li>
    <?php endforeach; ?>
</ul>