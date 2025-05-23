const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';
  
  return {
    entry: {
      // Frontend assets
      'frontend': './src/js/frontend.js',
      'frontend-style': './src/scss/frontend.scss',
      
      // Admin assets
      'admin': './src/js/admin.js',
      'admin-style': './src/scss/admin.scss',
      
      // Maps specific
      'maps': './src/js/maps.js',
      'maps-style': './src/scss/maps.scss',
      
      // Validation
      'validation': './src/js/validation.js',
      
      // Charts and statistics
      'charts': './src/js/charts.js',
      'charts-style': './src/scss/charts.scss'
    },
    
    output: {
      path: path.resolve(__dirname, '../assets'),
      filename: 'js/[name].min.js',
      publicPath: '',
      clean: false // Don't clean everything, just our built files
    },
    
    module: {
      rules: [
        // JavaScript/ES6+ compilation
        {
          test: /\.js$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: [
                ['@babel/preset-env', {
                  targets: {
                    browsers: ['> 1%', 'last 2 versions', 'ie >= 11']
                  },
                  modules: false,
                  useBuiltIns: 'usage',
                  corejs: 3
                }]
              ],
              plugins: [
                '@babel/plugin-proposal-object-rest-spread',
                '@babel/plugin-proposal-class-properties'
              ]
            }
          }
        },
        
        // SCSS/CSS processing
        {
          test: /\.(scss|sass|css)$/,
          use: [
            MiniCssExtractPlugin.loader,
            {
              loader: 'css-loader',
              options: {
                sourceMap: !isProduction,
                importLoaders: 2
              }
            },
            {
              loader: 'postcss-loader',
              options: {
                sourceMap: !isProduction,
                postcssOptions: {
                  plugins: [
                    require('autoprefixer'),
                    ...(isProduction ? [require('cssnano')] : [])
                  ]
                }
              }
            },
            {
              loader: 'sass-loader',
              options: {
                sourceMap: !isProduction,
                sassOptions: {
                  outputStyle: isProduction ? 'compressed' : 'expanded',
                  includePaths: [
                    path.resolve(__dirname, '../src/scss'),
                    path.resolve(__dirname, 'node_modules')
                  ]
                }
              }
            }
          ]
        },
        
        // Images
        {
          test: /\.(png|jpe?g|gif|svg)$/i,
          type: 'asset',
          parser: {
            dataUrlCondition: {
              maxSize: 8 * 1024 // 8kb
            }
          },
          generator: {
            filename: 'images/[name][ext]'
          }
        },
        
        // Fonts
        {
          test: /\.(woff|woff2|eot|ttf|otf)$/i,
          type: 'asset/resource',
          generator: {
            filename: 'fonts/[name][ext]'
          }
        }
      ]
    },
    
    plugins: [
      // Clean build directory
      new CleanWebpackPlugin({
        cleanOnceBeforeBuildPatterns: [
          '../assets/js/*.min.js',
          '../assets/css/*.min.css'
        ],
        dangerouslyAllowCleanPatternsOutsideProject: true,
        dry: false
      }),
      
      // Extract CSS
      new MiniCssExtractPlugin({
        filename: 'css/[name].min.css',
        chunkFilename: 'css/[id].css'
      }),
      
      // Copy static assets
      new CopyWebpackPlugin({
        patterns: [
          {
            from: path.resolve(__dirname, '../src/images'),
            to: path.resolve(__dirname, '../assets/images'),
            noErrorOnMissing: true
          },
          {
            from: path.resolve(__dirname, '../src/fonts'),
            to: path.resolve(__dirname, '../assets/fonts'),
            noErrorOnMissing: true
          }
        ]
      }),
      
      // Bundle analyzer (only in analyze mode)
      ...(process.env.ANALYZE === 'true' ? [
        new BundleAnalyzerPlugin({
          analyzerMode: 'static',
          openAnalyzer: false,
          reportFilename: '../build/bundle-report.html'
        })
      ] : [])
    ],
    
    optimization: {
      minimizer: [
        // JavaScript minification
        new TerserPlugin({
          test: /\.js(\?.*)?$/i,
          exclude: /node_modules/,
          terserOptions: {
            compress: {
              drop_console: isProduction,
              drop_debugger: isProduction
            },
            format: {
              comments: false
            }
          },
          extractComments: false
        }),
        
        // CSS minification
        new CssMinimizerPlugin({
          test: /\.css$/i,
          minimizerOptions: {
            preset: [
              'default',
              {
                discardComments: { removeAll: true }
              }
            ]
          }
        })
      ],
      
      splitChunks: {
        chunks: 'all',
        cacheGroups: {
          // Vendor libraries
          vendor: {
            test: /[\\/]node_modules[\\/]/,
            name: 'vendors',
            chunks: 'all',
            priority: 10
          },
          
          // Common code between admin and frontend
          common: {
            name: 'common',
            minChunks: 2,
            chunks: 'all',
            priority: 5,
            reuseExistingChunk: true
          },
          
          // CSS splitting
          styles: {
            type: 'css/mini-extract',
            chunks: 'all',
            enforce: true
          }
        }
      }
    },
    
    resolve: {
      extensions: ['.js', '.jsx', '.scss', '.css'],
      alias: {
        '@': path.resolve(__dirname, '../src'),
        '@js': path.resolve(__dirname, '../src/js'),
        '@scss': path.resolve(__dirname, '../src/scss'),
        '@images': path.resolve(__dirname, '../src/images'),
        '@fonts': path.resolve(__dirname, '../src/fonts')
      }
    },
    
    externals: {
      // WordPress globals
      'jquery': 'jQuery',
      'wp': 'wp',
      'lodash': '_',
      
      // Leaflet (loaded separately)
      'leaflet': 'L'
    },
    
    devtool: isProduction ? false : 'source-map',
    
    performance: {
      hints: isProduction ? 'warning' : false,
      maxEntrypointSize: 250000,
      maxAssetSize: 250000
    },
    
    stats: {
      colors: true,
      modules: false,
      children: false,
      chunks: false,
      chunkModules: false
    },
    
    // Development server (for testing)
    devServer: {
      static: {
        directory: path.join(__dirname, '../assets')
      },
      compress: true,
      port: 3000,
      hot: true,
      open: false,
      headers: {
        'Access-Control-Allow-Origin': '*'
      }
    }
  };
};