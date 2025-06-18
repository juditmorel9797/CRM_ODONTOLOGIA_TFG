
CREATE TABLE referido (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) UNIQUE NOT NULL
);

CREATE TABLE perfil (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL
);

CREATE TABLE permiso (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL
);

CREATE TABLE perfil_permiso (
    id_perfil INT,
    id_permiso INT,
    PRIMARY KEY (id_perfil, id_permiso),
    FOREIGN KEY (id_perfil) REFERENCES perfil(id),
    FOREIGN KEY (id_permiso) REFERENCES permiso(id)
);

CREATE TABLE categoria_tratamiento (
    id CHAR(36) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

CREATE TABLE tarifa (
    id CHAR(36) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT
);

CREATE TABLE tratamiento_base (
    id CHAR(36) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    duracion_minutos INT NOT NULL
);

CREATE TABLE estado_cita (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    color_hex CHAR(7) NOT NULL,
    icono VARCHAR(80) NOT NULL DEFAULT ''
);

CREATE TABLE franja_horaria (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    UNIQUE (hora_inicio, hora_fin)
);

CREATE TABLE usuario (
    id CHAR(36) PRIMARY KEY,
    user_name VARCHAR(50) UNIQUE NOT NULL,
    password CHAR(15) NOT NULL,
    id_perfil INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    nombre_visible VARCHAR(100),
    FOREIGN KEY (id_perfil) REFERENCES perfil(id)
);

CREATE TABLE paciente (
    id CHAR(36) PRIMARY KEY,
    nhc INT UNIQUE NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    apellido1 VARCHAR(50) NOT NULL,
    apellido2 VARCHAR(50),
    fecha_nacimiento DATE NOT NULL,
    telefono VARCHAR(20),
    correo VARCHAR(100),
    dni VARCHAR(20),
    direccion VARCHAR(255),
    cp CHAR(5),
    provincia VARCHAR(100),
    localidad VARCHAR(100),
    sexo ENUM('M', 'F'),
    id_referido INT,
    fecha_alta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_referido) REFERENCES referido(id)
);

CREATE TABLE paciente_primerasv (
    id CHAR(36) PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observaciones TEXT
);


CREATE TABLE consentimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    id_paciente CHAR(36) NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    archivo_nombre VARCHAR(255) NOT NULL,
    archivo_tipo VARCHAR(40) NOT NULL,
    archivo LONGTEXT NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    subido_por CHAR(36),
    FOREIGN KEY (id_paciente) REFERENCES paciente(id),
    FOREIGN KEY (subido_por) REFERENCES usuario(id)
);

CREATE TABLE documento_administrativo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    id_paciente CHAR(36) NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    archivo_nombre VARCHAR(255) NOT NULL,
    archivo_tipo VARCHAR(40) NOT NULL,
    archivo LONGTEXT NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    subido_por CHAR(36),
    FOREIGN KEY (id_paciente) REFERENCES paciente(id),
    FOREIGN KEY (subido_por) REFERENCES usuario(id)
);

CREATE TABLE tratamiento (
    id CHAR(36) PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    id_tarifa CHAR(36),
    id_categoria CHAR(36),
    requiere_diente TINYINT(1) DEFAULT 0,
    descripcion TEXT,
    activo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_tarifa) REFERENCES tarifa(id),
    FOREIGN KEY (id_categoria) REFERENCES categoria_tratamiento(id)
);

CREATE TABLE presupuesto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    id_paciente CHAR(36) NOT NULL,
    id_usuario CHAR(36),
    id_tarifa CHAR(36),
    estado ENUM('ENTREGADO','ACEPTADO','RECHAZADO','EN_CURSO','DOCUMENTACION','FINANCIERA','KO_FINANCIERA','NO_LOCALIZADO') DEFAULT 'ENTREGADO',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_estado DATE,
    valor_caja DECIMAL(10,2) DEFAULT 0,
    fecha_caja DATE,
    observaciones TEXT,
    FOREIGN KEY (id_paciente) REFERENCES paciente(id),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id),
    FOREIGN KEY (id_tarifa) REFERENCES tarifa(id)
);

CREATE TABLE presupuesto_tratamiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    id_presupuesto CHAR(36) NOT NULL,
    id_tratamiento CHAR(36) NOT NULL,
    diente VARCHAR(50),
    estado ENUM('pendiente', 'realizado', 'cancelado') DEFAULT 'pendiente',
    precio_unitario DECIMAL(10,2) DEFAULT 0,
    cantidad INT DEFAULT 1,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_presupuesto) REFERENCES presupuesto(uuid) ON DELETE CASCADE,
    FOREIGN KEY (id_tratamiento) REFERENCES tratamiento(id)
);


CREATE TABLE historial_clinico (
    id CHAR(36) PRIMARY KEY,
    id_paciente CHAR(36) NOT NULL,
    id_presupuesto CHAR(36),  -- Este es un UUID, no un INT
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    descripcion TEXT,
    total_presupuesto DECIMAL(10,2) DEFAULT 0,
    saldo_restante DECIMAL(10,2) DEFAULT 0,
    estado ENUM('activo', 'finalizado') DEFAULT 'activo',
    creado_por CHAR(36) DEFAULT NULL,
    FOREIGN KEY (id_paciente) REFERENCES paciente(id),
    FOREIGN KEY (id_presupuesto) REFERENCES presupuesto(uuid),
    FOREIGN KEY (creado_por) REFERENCES usuario(id)
);

CREATE TABLE comentario_historial (
    id CHAR(36) PRIMARY KEY,
    id_historial CHAR(36) NOT NULL,
    id_usuario CHAR(36) NOT NULL,
    texto TEXT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_historial) REFERENCES historial_clinico(id),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id)
);

CREATE TABLE radiografias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente CHAR(36) NOT NULL,
    fecha DATE NOT NULL,
    imagen_base64 LONGTEXT NOT NULL,
    FOREIGN KEY (id_paciente) REFERENCES paciente(id) ON DELETE CASCADE
);

CREATE TABLE diagnostico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_radiografia INT NOT NULL,
    id_paciente CHAR(36) NOT NULL,
    id_llamada_api VARCHAR(64) NOT NULL,
    edad_paciente INT,
    diagnostico TEXT,
    tokens INT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    tratamiento_recomendado LONGTEXT,
    FOREIGN KEY (id_radiografia) REFERENCES radiografias(id) ON DELETE CASCADE,
    FOREIGN KEY (id_paciente) REFERENCES paciente(id) ON DELETE CASCADE
);

CREATE TABLE agenda (
    id CHAR(36) PRIMARY KEY,
    id_doctor CHAR(36) NOT NULL,
    dias_laborales SET('L', 'M', 'X', 'J', 'V', 'S') NOT NULL,
    inicio_manana TIME,
    fin_manana TIME,
    inicio_tarde TIME,
    fin_tarde TIME,
    activo BOOLEAN DEFAULT TRUE,
    nombre_agenda VARCHAR(100),
    FOREIGN KEY (id_doctor) REFERENCES usuario(id)
);

CREATE TABLE cita (
    id CHAR(36) PRIMARY KEY,
    id_paciente CHAR(36) DEFAULT NULL,
    id_pv CHAR(36) DEFAULT NULL,
    id_agenda CHAR(36) NOT NULL,
    id_tratamiento_base CHAR(36),
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    observaciones TEXT,
    id_estado_cita INT NOT NULL,
    creado_por CHAR(36),
    FOREIGN KEY (id_paciente) REFERENCES paciente(id),
    FOREIGN KEY (id_pv) REFERENCES paciente_primerasv(id),
    FOREIGN KEY (id_agenda) REFERENCES agenda(id),
    FOREIGN KEY (id_tratamiento_base) REFERENCES tratamiento_base(id),
    FOREIGN KEY (id_estado_cita) REFERENCES estado_cita(id),
    CONSTRAINT chk_paciente_or_pv CHECK (
        (id_paciente IS NOT NULL AND id_pv IS NULL) OR
        (id_paciente IS NULL AND id_pv IS NOT NULL)
    )
);

CREATE TABLE doctor_franja (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_doctor CHAR(36) NOT NULL,
    id_franja INT NOT NULL,
    dia ENUM('L', 'M', 'X', 'J', 'V', 'S') NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_doctor) REFERENCES usuario(id),
    FOREIGN KEY (id_franja) REFERENCES franja_horaria(id)
);

CREATE TABLE bloqueo_doctor (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_doctor CHAR(36) NOT NULL,
    fecha DATE NOT NULL,
    motivo VARCHAR(255),
    FOREIGN KEY (id_doctor) REFERENCES usuario(id)
);
