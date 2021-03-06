#!groovy

def launchIntegrationTests = "no"

class Globals {
    static pimVersion = "2.0"
    static extensionBranch = "2.0.x-dev"
    static mysqlVersion = "5.7"
}

stage("Checkout") {
    milestone 1
    if (env.BRANCH_NAME =~ /^PR-/) {
        launchIntegrationTests = input(message: 'Launch tests?', parameters: [
            choice(choices: 'yes\nno', description: 'Run integration tests', name: 'launchIntegrationTests'),
        ])
    }

    milestone 2
    node {
        deleteDir()
        checkout scm
        stash "icecat_extension"

        checkout([$class: 'GitSCM',
             branches: [[name: "${Globals.pimVersion}"]],
             userRemoteConfigs: [[credentialsId: 'github-credentials', url: 'https://github.com/akeneo/pim-enterprise-dev.git']]
        ])
        stash "pim_enterprise"
   }
}

if (launchIntegrationTests.equals("yes")) {
    stage("Integration tests") {
        def tasks = [:]

        tasks["phpunit-7.1-ee"] = {runIntegrationTest("7.1", "${Globals.mysqlVersion}")}

        parallel tasks
    }
}

def runIntegrationTest(phpVersion, mysqlVersion) {
    node('docker') {
        cleanUpEnvironment()

        try {
            docker.image("elasticsearch:5.5")
            .withRun("--name elasticsearch -e ES_JAVA_OPTS=\"-Xms512m -Xmx512m\"") {
                docker.image("mysql:5.7")
                .withRun("--name mysql -e MYSQL_ROOT_PASSWORD=root -e MYSQL_USER=akeneo_pim -e MYSQL_PASSWORD=akeneo_pim -e MYSQL_DATABASE=akeneo_pim --tmpfs=/var/lib/mysql/:rw,noexec,nosuid,size=1000m --tmpfs=/tmp/:rw,noexec,nosuid,size=300m") {
                    docker.image("akeneo/php:${phpVersion}")
                    .inside("--link mysql:mysql --link elasticsearch:elasticsearch -v /home/akeneo/.composer:/home/docker/.composer -e COMPOSER_HOME=/home/docker/.composer") {
                        unstash "pim_enterprise"

                        sh """
                            composer config repositories.icecat '{"type": "vcs", "url": "git@github.com:akeneo/icecat-connector.git", "branch": "master"}'
                            php -d memory_limit=3G /usr/local/bin/composer require phpunit/phpunit:5.4.* akeneo/icecat-connector:${Globals.extensionBranch} --no-interaction --no-progress --prefer-dist
                        """

                        dir("vendor/akeneo/icecat-connector") {
                            deleteDir()
                            unstash "icecat_extension"
                        }
                        sh 'composer dump-autoload -o'

                        sh """
                            rm app/cache/* -rf
                            sed -i 's#// your app bundles should be registered here#\\0\\nnew Pim\\\\Bundle\\\\IcecatConnectorBundle\\\\PimIcecatConnectorBundle(),#' app/AppKernel.php
                            sed -i 's#// your app bundles should be registered here#\\0\\nnew Pim\\\\Bundle\\\\ExtendedMeasureBundle\\\\PimExtendedMeasureBundle(),#' app/AppKernel.php
                            sed -i 's#// your app bundles should be registered here#\\0\\nnew Pim\\\\Bundle\\\\ExtendedAttributeTypeBundle\\\\PimExtendedAttributeTypeBundle(),#' app/AppKernel.php
                            cat app/AppKernel.php
                        """

                        sh """
                            cp vendor/akeneo/icecat-connector/src/Resources/jenkins/parameters_test.yml app/config/parameters_test.yml
                            cat vendor/akeneo/icecat-connector/src/Resources/jenkins/routing.yml >> app/config/routing.yml
                            cp vendor/akeneo/icecat-connector/src/Resources/jenkins/phpunit.xml app/phpunit.xml
                            cat vendor/akeneo/icecat-connector/src/Resources/jenkins/config_test.yml >> app/config/config_test.yml
                            mkdir -p app/build/logs
                        """
                        sh "sleep 10"
                        sh "bin/console pim:install --force --env=test"
                        sh "vendor/bin/phpunit -c app/phpunit.xml --log-junit app/build/logs/phpunit.xml"
                    }
                }
            }
        } finally {
            junit "app/build/logs/*.xml"
            deleteDir()
        }
    }
}

def cleanUpEnvironment() {
    deleteDir()
    sh '''
        docker ps -a -q | xargs -n 1 -P 8 -I {} docker rm -f {} > /dev/null
        docker volume ls -q | xargs -n 1 -P 8 -I {} docker volume rm {} > /dev/null
        docker network ls --filter name=akeneo -q | xargs -n 1 -P 8 -I {} docker network rm {} > /dev/null
    '''
}
