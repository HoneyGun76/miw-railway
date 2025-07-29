<?php
/**
 * Testing Dashboard - Central hub for all form testing activities
 */

require_once 'config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIW Travel Forms Testing Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .dashboard {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .testing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .testing-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            border-left: 5px solid #667eea;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .testing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .testing-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        
        .testing-card p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #7e8d94 0%, #9ba7b1 100%);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ff9800 0%, #e68900 100%);
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .status-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .status-card:hover {
            border-color: #667eea;
        }
        
        .status-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .status-label {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .manual-testing {
            background: #f0f8ff;
            border-radius: 10px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .manual-testing h3 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .form-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .form-link {
            display: block;
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-link:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .form-link h4 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .form-link p {
            color: #666;
            font-size: 0.9em;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            border-top: 1px solid #e9ecef;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            border-left: 5px solid;
        }
        
        .alert-info {
            background: #e7f3ff;
            border-color: #2196F3;
            color: #1976D2;
        }
        
        .alert-success {
            background: #e8f5e8;
            border-color: #4CAF50;
            color: #2E7D32;
        }
        
        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 1.5em;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }
        
        .refresh-btn:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <h1>🧪 MIW Travel Forms Testing Dashboard</h1>
            <p>Comprehensive testing suite for Haji and Umroh registration forms</p>
        </div>
        
        <div class="content">
            <div class="alert alert-info">
                <strong>🎯 Testing Objective:</strong> Validate both Haji and Umroh forms through white box and black box testing methodologies, ensuring complete workflow from form submission to invoice generation.
            </div>
            
            <div class="status-grid">
                <div class="status-card">
                    <div class="status-number">2</div>
                    <div class="status-label">Forms Available</div>
                </div>
                <div class="status-card">
                    <div class="status-number">3</div>
                    <div class="status-label">Testing Suites</div>
                </div>
                <div class="status-card">
                    <div class="status-number">50+</div>
                    <div class="status-label">Test Scenarios</div>
                </div>
                <div class="status-card">
                    <div class="status-number">100%</div>
                    <div class="status-label">Coverage Goal</div>
                </div>
            </div>
            
            <div class="testing-grid">
                <div class="testing-card">
                    <h3>🔬 White Box Testing</h3>
                    <p>Comprehensive internal code structure testing including database connections, validation logic, data type handling, and file upload mechanisms.</p>
                    <a href="comprehensive_form_testing.php" class="btn" target="_blank">Run White Box Tests</a>
                </div>
                
                <div class="testing-card">
                    <h3>🎯 Black Box Testing</h3>
                    <p>User-focused testing with valid/invalid inputs, boundary values, special characters, and security validation scenarios.</p>
                    <a href="black_box_testing.php" class="btn" target="_blank">Run Black Box Tests</a>
                </div>
                
                <div class="testing-card">
                    <h3>🔄 End-to-End Testing</h3>
                    <p>Complete user journey simulation from form submission through invoice generation, testing the entire workflow integration.</p>
                    <a href="end_to_end_testing.php" class="btn" target="_blank">Run E2E Tests</a>
                </div>
                
                <div class="testing-card">
                    <h3>📊 Error Monitoring</h3>
                    <p>Real-time error logging and monitoring system to track issues during testing and production usage.</p>
                    <a href="error_logger.php" class="btn btn-warning" target="_blank">View Error Logger</a>
                </div>
                
                <div class="testing-card">
                    <h3>🗄️ Database Status</h3>
                    <p>Database connection diagnostics, table structure validation, and data integrity checks.</p>
                    <a href="database_diagnostic.php" class="btn btn-secondary" target="_blank">Check Database</a>
                </div>
                
                <div class="testing-card">
                    <h3>📈 System Information</h3>
                    <p>Heroku environment details, PHP configuration, and system performance metrics.</p>
                    <a href="deploy_debug.php" class="btn btn-secondary" target="_blank">System Info</a>
                </div>
            </div>
            
            <div class="manual-testing">
                <h3>👤 Manual Testing Forms</h3>
                <p>Test the actual forms manually to validate user experience and workflow:</p>
                
                <div class="form-links">
                    <a href="form_haji.php" class="form-link" target="_blank">
                        <h4>🕋 Haji Registration Form</h4>
                        <p>Test the complete Haji pilgrimage registration process with all required fields and validations.</p>
                    </a>
                    
                    <a href="form_umroh.php" class="form-link" target="_blank">
                        <h4>🕌 Umroh Registration Form</h4>
                        <p>Test the Umroh pilgrimage registration process with specialized field requirements.</p>
                    </a>
                    
                    <a href="invoice.php?nama=Test%20User&program_pilihan=Test%20Package&payment_total=5000" class="form-link" target="_blank">
                        <h4>🧾 Invoice Preview</h4>
                        <p>Test the invoice generation page with sample parameters to verify layout and functionality.</p>
                    </a>
                    
                    <a href="admin_dashboard.php" class="form-link" target="_blank">
                        <h4>⚙️ Admin Dashboard</h4>
                        <p>Access administrative functions for managing registrations and monitoring system status.</p>
                    </a>
                </div>
            </div>
            
            <div class="alert alert-success" style="margin-top: 30px;">
                <strong>✅ Testing Features:</strong>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>Form validation (required fields, formats, data types)</li>
                    <li>Database integration (connections, queries, transactions)</li>
                    <li>Security testing (SQL injection, XSS protection)</li>
                    <li>Edge case handling (empty fields, special characters)</li>
                    <li>Invoice generation workflow validation</li>
                    <li>Error logging and monitoring</li>
                    <li>Performance and reliability testing</li>
                </ul>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                <h3>📋 Testing Checklist</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-top: 15px;">
                    <div>
                        <h4>✅ Form Validation</h4>
                        <ul style="margin-left: 20px; color: #666;">
                            <li>Required field validation</li>
                            <li>NIK format validation (16 digits)</li>
                            <li>Email format validation</li>
                            <li>Date validation</li>
                            <li>Integer field handling</li>
                        </ul>
                    </div>
                    <div>
                        <h4>✅ Database Operations</h4>
                        <ul style="margin-left: 20px; color: #666;">
                            <li>Connection stability</li>
                            <li>Data insertion</li>
                            <li>Package retrieval</li>
                            <li>Transaction handling</li>
                            <li>Error logging</li>
                        </ul>
                    </div>
                    <div>
                        <h4>✅ Security Features</h4>
                        <ul style="margin-left: 20px; color: #666;">
                            <li>SQL injection protection</li>
                            <li>XSS prevention</li>
                            <li>Input sanitization</li>
                            <li>CSRF protection</li>
                            <li>Data validation</li>
                        </ul>
                    </div>
                    <div>
                        <h4>✅ User Experience</h4>
                        <ul style="margin-left: 20px; color: #666;">
                            <li>Form usability</li>
                            <li>Error messaging</li>
                            <li>Invoice generation</li>
                            <li>Mobile responsiveness</li>
                            <li>Performance</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>MIW Travel Forms Testing Dashboard | Last Updated: <?php echo date('Y-m-d H:i:s'); ?></p>
            <p>Environment: Heroku Production | Database: PostgreSQL | PHP: <?php echo PHP_VERSION; ?></p>
        </div>
    </div>
    
    <button class="refresh-btn" onclick="location.reload()" title="Refresh Dashboard">
        🔄
    </button>
    
    <script>
        // Auto-refresh every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);
        
        // Add click tracking for testing buttons
        document.querySelectorAll('.btn, .form-link').forEach(function(btn) {
            btn.addEventListener('click', function() {
                console.log('Testing action:', this.href || this.textContent);
            });
        });
    </script>
</body>
</html>
