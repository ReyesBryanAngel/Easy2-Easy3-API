pipeline {
    agent {
        label any// This label should match your Jenkins agent's label
    }

    environment {
        PHP_VERSION = '8.3'
    }

    stages {
        stage('Checkout Code') {
            steps {
                // Checkout the code from the repository
                checkout scm
            }
        }

        stage('Set Up PHP') {
            steps {
                script {
                    // Use a specific PHP version
                    // This assumes you have a setup-php plugin or similar
                    // You may need to install the PHP tool manually or use a different method
                    echo "Setting up PHP version ${env.PHP_VERSION}"
                    sh "brew install php@${env.PHP_VERSION}"  // Example using Homebrew
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                // Install PHP dependencies
                sh 'composer install --prefer-dist --no-progress --no-interaction'
            }
        }

        stage('Run Tests') {
            steps {
                // Run PHPUnit tests
                sh 'vendor/bin/phpunit'
            }
        }
    }

    post {
        always {
            // Clean up or perform any actions that should run regardless of success or failure
            echo 'Pipeline finished.'
        }
    }
}
