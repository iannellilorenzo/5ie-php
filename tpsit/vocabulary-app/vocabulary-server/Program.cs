using System;
using System.Collections.Generic;
using System.Net;
using System.Net.Sockets;
using System.Text;
using System.Threading;
using System.Text.Json;

namespace VocabularyServer
{
    class Program
    {
        private static Dictionary<string, string> vocabulary = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase)
        {
            { "hello", "a greeting or salutation" },
            { "computer", "an electronic device for storing and processing data" },
            { "network", "a group of interconnected computers or devices" }
        };
        
        private static readonly object vocabularyLock = new object();

        static void Main(string[] args)
        {
            TcpListener server = null;
            
            try
            {
                // Set the TcpListener on port 13000
                int port = 13000;
                IPAddress localAddr = IPAddress.Parse("127.0.0.1");
                
                // TcpListener server = new TcpListener(port);
                server = new TcpListener(localAddr, port);

                // Start listening for client requests
                server.Start();
                
                Console.WriteLine("Vocabulary Server started on port 13000");
                Console.WriteLine("Press Ctrl+C to stop the server");
                
                // Enter the listening loop
                while (true)
                {
                    Console.WriteLine("Waiting for a connection...");
                    
                    // Perform a blocking call to accept requests
                    TcpClient client = server.AcceptTcpClient();
                    Console.WriteLine("Connected to a client!");
                    
                    // Create a thread to handle the client
                    Thread clientThread = new Thread(new ParameterizedThreadStart(HandleClient));
                    clientThread.Start(client);
                }
            }
            catch (SocketException e)
            {
                Console.WriteLine("SocketException: {0}", e);
            }
            finally
            {
                // Stop listening for new clients
                server?.Stop();
            }
        }
        
        static void HandleClient(object obj)
        {
            TcpClient client = (TcpClient)obj;
            NetworkStream stream = client.GetStream();
            
            byte[] buffer = new byte[1024];
            int bytesRead;
            
            try
            {
                // Loop to receive all the data sent by the client
                while ((bytesRead = stream.Read(buffer, 0, buffer.Length)) != 0)
                {
                    // Translate data bytes to a string
                    string data = Encoding.UTF8.GetString(buffer, 0, bytesRead);
                    Console.WriteLine("Received: {0}", data);
                    
                    // Process the request
                    string response = ProcessRequest(data);
                    
                    // Send back a response
                    byte[] msg = Encoding.UTF8.GetBytes(response);
                    stream.Write(msg, 0, msg.Length);
                    Console.WriteLine("Sent: {0}", response);
                }
            }
            catch (Exception e)
            {
                Console.WriteLine("Exception: {0}", e.ToString());
            }
            finally
            {
                // Close the connection
                client.Close();
                Console.WriteLine("Client disconnected");
            }
        }
        
        static string ProcessRequest(string request)
        {
            try
            {
                var requestObj = JsonSerializer.Deserialize<Dictionary<string, string>>(request);
                
                if (requestObj.ContainsKey("action"))
                {
                    switch (requestObj["action"].ToLower())
                    {
                        case "lookup":
                            if (requestObj.ContainsKey("word"))
                            {
                                string word = requestObj["word"];
                                lock (vocabularyLock)
                                {
                                    if (vocabulary.TryGetValue(word, out string definition))
                                    {
                                        return JsonSerializer.Serialize(new { status = "success", word, definition });
                                    }
                                    return JsonSerializer.Serialize(new { status = "error", message = "Word not found" });
                                }
                            }
                            break;
                            
                        case "add":
                            if (requestObj.ContainsKey("word") && requestObj.ContainsKey("definition"))
                            {
                                string word = requestObj["word"];
                                string definition = requestObj["definition"];
                                
                                lock (vocabularyLock)
                                {
                                    vocabulary[word] = definition;
                                }
                                
                                return JsonSerializer.Serialize(new { status = "success", message = "Word added successfully" });
                            }
                            break;
                            
                        case "list":
                            lock (vocabularyLock)
                            {
                                return JsonSerializer.Serialize(new { status = "success", words = vocabulary });
                            }
                    }
                }
                
                return JsonSerializer.Serialize(new { status = "error", message = "Invalid request format" });
            }
            catch (JsonException)
            {
                return JsonSerializer.Serialize(new { status = "error", message = "Invalid JSON format" });
            }
            catch (Exception ex)
            {
                return JsonSerializer.Serialize(new { status = "error", message = ex.Message });
            }
        }
    }
}