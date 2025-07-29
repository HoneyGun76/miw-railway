-- PostgreSQL Database Schema for MIW Travel Management System
-- Converted from MySQL schema for Heroku deployment

-- Drop existing tables if they exist
DROP TABLE IF EXISTS data_invoice CASCADE;
DROP TABLE IF EXISTS data_jamaah CASCADE;
DROP TABLE IF EXISTS data_paket CASCADE;
DROP TABLE IF EXISTS data_pembatalan CASCADE;

-- Create data_paket table first (referenced by other tables)
CREATE TABLE data_paket (
    pak_id SERIAL PRIMARY KEY,
    jenis_paket VARCHAR(10) NOT NULL CHECK (jenis_paket IN ('Haji', 'Umroh')),
    currency VARCHAR(3) NOT NULL CHECK (currency IN ('IDR', 'USD')),
    program_pilihan VARCHAR(255),
    tanggal_keberangkatan DATE,
    base_price_quad DECIMAL(15,2),
    base_price_triple DECIMAL(15,2),
    base_price_double DECIMAL(15,2),
    hotel_medinah VARCHAR(100),
    hotel_makkah VARCHAR(100),
    additional_hotels VARCHAR(100),
    hotel_medinah_rooms TEXT,
    hotel_makkah_rooms TEXT,
    additional_hotels_rooms TEXT,
    room_numbers TEXT,
    hcn TEXT, -- Stores hotel confirmation numbers in JSON format
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create data_invoice table
CREATE TABLE data_invoice (
    invoice_id VARCHAR(8) PRIMARY KEY,
    pak_id INTEGER REFERENCES data_paket(pak_id),
    nik VARCHAR(16),
    nama VARCHAR(100),
    alamat TEXT,
    no_telp VARCHAR(20),
    keterangan TEXT,
    payment_type VARCHAR(10) CHECK (payment_type IN ('DP', 'Pelunasan')),
    program_pilihan VARCHAR(255),
    type_room_pilihan VARCHAR(10) CHECK (type_room_pilihan IN ('Quad', 'Triple', 'Double')),
    harga_paket DECIMAL(15,2),
    payment_amount DECIMAL(15,2),
    diskon INTEGER,
    total_uang_masuk DECIMAL(15,2),
    sisa_pembayaran DECIMAL(15,2)
);

-- Create data_jamaah table
CREATE TABLE data_jamaah (
    nik VARCHAR(16) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    tempat_lahir VARCHAR(50),
    tanggal_lahir DATE,
    jenis_kelamin VARCHAR(10) CHECK (jenis_kelamin IN ('Laki-laki', 'Perempuan')),
    alamat TEXT,
    kode_pos VARCHAR(10),
    email VARCHAR(50),
    no_telp VARCHAR(20),
    tinggi_badan INTEGER,
    berat_badan INTEGER,
    nama_ayah VARCHAR(100),
    nama_ibu VARCHAR(100),
    umur INTEGER,
    kewarganegaraan VARCHAR(10) CHECK (kewarganegaraan IN ('Indonesia', 'Asing')),
    desa_kelurahan VARCHAR(100),
    kecamatan VARCHAR(100),
    kabupaten_kota VARCHAR(100),
    provinsi VARCHAR(100),
    pendidikan VARCHAR(20) CHECK (pendidikan IN ('SD', 'SLTP', 'SLTA', 'D1/D2/D3/SM', 'S1', 'S2', 'S3')),
    pekerjaan VARCHAR(30) CHECK (pekerjaan IN ('Pegawai Negeri Sipil', 'TNI/POLRI', 'Dagang', 'Tani/Nelayan', 'Swasta', 'Ibu Rumah Tangga', 'Pelajar/Mahasiswa', 'BUMN/BUMD', 'Pensiunan')),
    golongan_darah VARCHAR(2) CHECK (golongan_darah IN ('A', 'B', 'AB', 'O')),
    status_perkawinan VARCHAR(15) CHECK (status_perkawinan IN ('Belum Menikah', 'Menikah', 'Janda/Duda')),
    ciri_rambut VARCHAR(50),
    ciri_alis VARCHAR(50),
    ciri_hidung VARCHAR(50),
    ciri_muka VARCHAR(50),
    emergency_nama VARCHAR(255),
    emergency_hp VARCHAR(20),
    nama_mahram VARCHAR(100),
    hubungan_mahram VARCHAR(15) CHECK (hubungan_mahram IN ('Orang Tua', 'Anak', 'Suami/Istri', 'Mertua', 'Saudara Kandung')),
    nomor_mahram VARCHAR(20),
    nama_paspor VARCHAR(255),
    no_paspor VARCHAR(50),
    tempat_pembuatan_paspor VARCHAR(255),
    tanggal_pengeluaran_paspor DATE,
    tanggal_habis_berlaku DATE,
    nama_sertifikat_covid VARCHAR(255),
    jenis_vaksin_1 VARCHAR(100),
    jenis_vaksin_2 VARCHAR(100),
    jenis_vaksin_3 VARCHAR(100),
    tanggal_vaksin_1 DATE,
    tanggal_vaksin_2 DATE,
    tanggal_vaksin_3 DATE,
    pengalaman_haji VARCHAR(10) CHECK (pengalaman_haji IN ('Pernah', 'Belum')),
    marketing_nama VARCHAR(100),
    marketing_hp VARCHAR(20),
    marketing_type VARCHAR(20),
    kk_path VARCHAR(255),
    ktp_path VARCHAR(255),
    paspor_path VARCHAR(255),
    transfer_account_name VARCHAR(100),
    payment_time TIME,
    payment_date DATE,
    payment_type VARCHAR(10) CHECK (payment_type IN ('DP', 'Pelunasan')),
    payment_method VARCHAR(10) CHECK (payment_method IN ('BSI', 'BNI', 'Mandiri')),
    payment_status VARCHAR(10) CHECK (payment_status IN ('pending', 'verified', 'rejected')),
    payment_path VARCHAR(255),
    payment_total DECIMAL(15,2),
    payment_remaining DECIMAL(15,2),
    payment_verified_at TIMESTAMP,
    payment_rejected_at TIMESTAMP,
    payment_verified_by VARCHAR(100),
    bk_kuning_path VARCHAR(255), -- Path to buku kuning file
    foto_path VARCHAR(255), -- Path to photo file
    fc_ktp_path VARCHAR(255), -- Path to KTP copy file
    fc_ijazah_path VARCHAR(255), -- Path to diploma copy file
    fc_kk_path VARCHAR(255), -- Path to family card copy file
    fc_bk_nikah_path VARCHAR(255), -- Path to marriage book copy file
    fc_akta_lahir_path VARCHAR(255), -- Path to birth certificate copy file
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pak_id INTEGER REFERENCES data_paket(pak_id),
    type_room_pilihan VARCHAR(10) CHECK (type_room_pilihan IN ('Quad', 'Triple', 'Double')),
    request_khusus TEXT,
    room_prefix VARCHAR(5),
    medinah_room_number VARCHAR(5),
    mekkah_room_number VARCHAR(5),
    room_relation VARCHAR(30)
);

-- Create data_pembatalan table
CREATE TABLE data_pembatalan (
    pembatalan_id SERIAL PRIMARY KEY,
    nik VARCHAR(16) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    no_telp VARCHAR(20),
    email VARCHAR(50),
    alasan TEXT,
    kwitansi_path VARCHAR(255),
    proof_path VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX idx_data_jamaah_pak_id ON data_jamaah(pak_id);
CREATE INDEX idx_data_jamaah_payment_status ON data_jamaah(payment_status);
CREATE INDEX idx_data_jamaah_created_at ON data_jamaah(created_at);
CREATE INDEX idx_data_paket_jenis ON data_paket(jenis_paket);
CREATE INDEX idx_data_paket_tanggal ON data_paket(tanggal_keberangkatan);

-- Create trigger function for updating timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create triggers for auto-updating updated_at columns
CREATE TRIGGER update_data_paket_updated_at 
    BEFORE UPDATE ON data_paket 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_data_jamaah_updated_at 
    BEFORE UPDATE ON data_jamaah 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_data_pembatalan_updated_at 
    BEFORE UPDATE ON data_pembatalan 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Insert sample data for testing (optional)
-- You can uncomment these inserts for testing purposes

/*
INSERT INTO data_paket (jenis_paket, currency, program_pilihan, tanggal_keberangkatan, base_price_quad, base_price_triple, base_price_double, hotel_medinah, hotel_makkah, additional_hotels, room_numbers, hcn) VALUES
('Umroh', 'IDR', 'UMRAH AGUSTUS SAFAR MUBARAK GOLD', '2025-08-10', 32500000.00, 34000000.00, 36500000.00, 'Al Anshor Golden Tulip', 'Hilton Convention', '[]', 'Q1,Q2,Q3,Q4,T1,T2,D1,D2', '{"medinah":"Medinah-Testing-1","makkah":"Makkah-Testing-1","additional":[],"issued_date":"2025-12-31","expiry_date":"2025-12-31"}'),
('Haji', 'USD', 'Haji Signature 2026', '2026-06-12', 16000.00, 18500.00, 21000.00, 'Hotel Medinah Suites', 'Hilton Convention', '[]', 'Q1,Q2,Q3,Q4,T1,T2,D1', '{"medinah":"MAD-3209674-3245-325643","makkah":"MAK-32525-43654-324","additional":[],"issued_date":"2026-07-12","expiry_date":"2026-07-23"}');
*/

-- Grant permissions (if needed)
-- GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO your_user;
-- GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO your_user;
